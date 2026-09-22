export const paginate = (rows, count, page, limit, reqPath = '') => {
  const lastPage = Math.ceil(count / limit) || 1;
  const from = count === 0 ? null : (page - 1) * limit + 1;
  const to = Math.min(page * limit, count);

  return {
    current_page: page,
    data: rows,
    first_page_url: `${reqPath}?page=1`,
    from,
    last_page: lastPage,
    last_page_url: `${reqPath}?page=${lastPage}`,
    links: [],
    next_page_url: page < lastPage ? `${reqPath}?page=${page + 1}` : null,
    path: reqPath,
    per_page: limit,
    prev_page_url: page > 1 ? `${reqPath}?page=${page - 1}` : null,
    to,
    total: count,
  };
};
