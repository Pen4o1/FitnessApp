---
description: Rules for Expo Router structure and navigation
globs: app/**/*.tsx, app/**/*.ts
---
- The project uses Expo Router (file-based routing).
- Strictly adhere to the route group directory structure:
  - Use the `(auth)` folder for unauthenticated screens (Welcome, Login, Register).
  - Use the `(app)` folder for protected application screens (Dashboard, Diary, Profile).
- Implement conditional redirection based on whether an API Token is stored locally (if no token exists -> automatically redirect to `(auth)`).