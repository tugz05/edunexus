# Vue Router Setup Instructions

## Installation

First, install Vue Router as a dependency:

```bash
npm install vue-router@4
```

## Integration with Inertia.js

**Important Note:** This project currently uses Inertia.js for routing. The Vue Router setup provided is for internal authenticated routes only. You have two options:

### Option 1: Hybrid Approach (Recommended)
- Keep Inertia.js for public pages (Login, Register, Landing)
- Use Vue Router for authenticated internal pages (Student/Teacher dashboards)
- You'll need to conditionally mount Vue Router only after authentication

### Option 2: Full Vue Router Migration
- Replace Inertia.js routing entirely with Vue Router
- Requires updating all existing pages to use Vue Router

## Integration Steps

### 1. Update `app.ts` to include Vue Router

You'll need to modify `resources/js/app.ts` to conditionally use Vue Router for authenticated routes. Here's an example approach:

```typescript
import { createInertiaApp } from '@inertiajs/vue3';
import { createRouter, createWebHistory } from 'vue-router';
import router from './router';

// ... existing code ...

// After user is authenticated, you can mount Vue Router
// This is a simplified example - adjust based on your auth flow
if (isAuthenticated()) {
    createApp({ render: () => h(App, props) })
        .use(plugin)
        .use(router) // Add Vue Router
        .mount(el);
} else {
    // Use Inertia for public pages
    createApp({ render: () => h(App, props) })
        .use(plugin)
        .mount(el);
}
```

### 2. Update User Model

Make sure your User model includes a `role` field. You'll need to:

1. Create a migration to add the `role` column:
```php
php artisan make:migration add_role_to_users_table
```

2. In the migration:
```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('role', ['student', 'teacher'])->default('student');
});
```

3. Update the User model to include `role` in `$fillable`:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
];
```

### 3. Update Router User Role Detection

The router currently uses a placeholder function `getUserRole()`. You'll need to update it to properly access the user role from your auth system. Options:

- If using Inertia: Access via `window.$page?.props?.auth?.user?.role`
- If using Vuex/Pinia: Access from store
- If using API: Fetch from API endpoint

### 4. Route Protection

The router includes navigation guards that:
- Check authentication status
- Verify user role matches route requirements
- Redirect unauthorized users appropriately

## Testing

1. Install Vue Router: `npm install vue-router@4`
2. Ensure user has a `role` field in the database
3. Test navigation between student/teacher routes
4. Verify bottom navigation appears correctly on mobile
5. Test role-based route protection

## File Structure Created

```
resources/js/
├── router/
│   └── index.ts              # Router configuration
├── layouts/
│   ├── StudentLayout.vue      # Student layout with bottom nav
│   └── TeacherLayout.vue      # Teacher layout with bottom nav
├── components/
│   └── navigation/
│       └── BottomNav.vue     # Reusable bottom navigation
└── pages/
    ├── student/
    │   ├── Home.vue
    │   ├── Content.vue
    │   ├── ContentDetail.vue
    │   ├── Recommendations.vue
    │   ├── Assistant.vue
    │   ├── Saved.vue
    │   └── Profile.vue
    └── teacher/
        ├── Home.vue
        ├── Content.vue
        ├── ContentCreate.vue
        ├── ContentEdit.vue
        ├── Assistant.vue
        └── Analytics.vue
```

