---
description: Rules for React Native components and styling
globs: components/**/*.tsx, app/**/*.tsx
---
- Use clean functional components paired with React Hooks.
- The user interface must be modern, clean, and consistent (proper padding/spacing and clear typography).
- For calorie rings, progress bars, and charts, use native performance-optimized libraries that ensure smooth animations (60 FPS) without blocking the JavaScript thread.
- All input fields in registration and tracking screens must enforce the correct keyboard type (e.g., `keyboardType="numeric"` for weight, height, and macro inputs).