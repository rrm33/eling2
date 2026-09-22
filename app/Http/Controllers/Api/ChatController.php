<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Shop;
use App\Models\User;

class ChatController extends Controller
{
    /**
     * Get list of conversations for Admin
     */
    public function getConversations(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shops = Shop::all()->map(function ($shop) {
            $latestMessage = Message::where('shop_id', $shop->id)->latest()->first();
            $unreadCount = Message::where('shop_id', $shop->id)
                ->where('is_read', false)
                ->whereHas('user', function ($q) {
                    $q->where('role', '!=', 'admin');
                })->count();

            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'latest_message' => $latestMessage ? [
                    'message' => $latestMessage->message,
                    'created_at' => $latestMessage->created_at,
                    'sender' => $latestMessage->user->role,
                ] : null,
                'unread_count' => $unreadCount,
            ];
        });

        // Sort by latest message date descending
        $sortedShops = $shops->sortByDesc(function ($shop) {
            return $shop['latest_message'] ? $shop['latest_message']['created_at'] : '0000-00-00';
        })->values();

        return response()->json(['data' => $sortedShops]);
    }

    /**
     * Get messages for a specific shop
     */
    public function getMessages(Request $request, $shop_id = null)
    {
        $user = $request->user();
        $targetShopId = $user->role === 'admin' ? $shop_id : $user->shop_id;

        if (!$targetShopId) {
            return response()->json(['message' => 'Shop ID required'], 400);
        }

        $messages = Message::with('user:id,name,role')
            ->where('shop_id', $targetShopId)
            ->oldest() // chronological order
            ->get();

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'shop_id' => 'nullable|exists:shops,id'
        ]);

        $user = $request->user();
        $targetShopId = $user->role === 'admin' ? $request->shop_id : $user->shop_id;

        if (!$targetShopId) {
            return response()->json(['message' => 'Shop ID required'], 400);
        }

        $message = Message::create([
            'shop_id' => $targetShopId,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Send Push Notification
        // SEMENTARA SAYA MATIKAN TOTAL UNTUK MEMBUKTIKAN BAHWA FIREBASE YANG BIKIN HANG SERVERNYA
        /*
        app()->terminating(function () use ($user, $targetShopId, $request) {
            try {
                $messaging = app('firebase.messaging');
                
                $targetUsers = \App\Models\User::whereNotNull('fcm_token');
                if ($user->role === 'admin') {
                    $targetUsers->where('shop_id', $targetShopId)->where('role', '!=', 'admin');
                } else {
                    $targetUsers->where('role', 'admin');
                }
                
                $tokens = $targetUsers->pluck('fcm_token')->toArray();
                
                if (!empty($tokens)) {
                    $notification = \Kreait\Firebase\Messaging\Notification::create('Pesan Baru dari ' . $user->name, $request->message);
                    $messageData = [
                        'shop_id' => (string) $targetShopId,
                        'type' => 'chat'
                    ];
                    
                    $cloudMessage = \Kreait\Firebase\Messaging\CloudMessage::new()
                        ->withNotification($notification)
                        ->withData($messageData);

                    $messaging->sendMulticast($cloudMessage, $tokens);
                }
            } catch (\Throwable $e) {
                \Log::error('Firebase Push Notification Error: ' . $e->getMessage());
            }
        });
        */

        return response()->json([
            'message' => 'Message sent',
            'data' => $message
        ], 201);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $shop_id = null)
    {
        $user = $request->user();
        $targetShopId = $user->role === 'admin' ? $shop_id : $user->shop_id;

        if (!$targetShopId) {
            return response()->json(['message' => 'Shop ID required'], 400);
        }

        Message::where('shop_id', $targetShopId)
            ->where('is_read', false)
            ->whereHas('user', function ($q) use ($user) {
                // If admin, mark non-admin messages as read
                // If non-admin, mark admin messages as read
                if ($user->role === 'admin') {
                    $q->where('role', '!=', 'admin');
                } else {
                    $q->where('role', 'admin');
                }
            })
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    /**
     * Get total unread count for the user
     */
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            $count = Message::where('is_read', false)
                ->whereHas('user', function ($q) {
                    $q->where('role', '!=', 'admin');
                })->count();
        } else {
            $count = Message::where('shop_id', $user->shop_id)
                ->where('is_read', false)
                ->whereHas('user', function ($q) {
                    $q->where('role', 'admin');
                })->count();
        }
        return response()->json(['unread_count' => $count]);
    }
}
