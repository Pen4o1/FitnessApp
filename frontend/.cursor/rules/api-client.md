---
description: Rules for API requests using Axios
globs: services/**/*.ts, hooks/**/*.ts
---
- All requests targeting the Laravel backend must flow through a centrally configured Axios client instance.
- The Axios base URL must point to the local server's IP address (be careful with `localhost` when testing on a real physical device or emulator; use the machine's actual local IP).
- For every request to a protected endpoint, automatically attach the Bearer Token to the `Authorization` header.
- Always catch API errors using `try/catch` blocks and present user-friendly error alerts (ensure proper Loading and Error states are handled).