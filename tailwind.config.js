/** @type {import('tailwindcss').Config} */
export default {
    content: ['./resources/**/*.blade.php', './resources/**/*.vue', './resources/**/*.js', './resources/**/*.ts'],
    safelist: [
        'bg-blue-100',
        'text-blue-600',
        'bg-indigo-100',
        'text-indigo-600',
        'bg-green-100',
        'text-green-600',
        'bg-red-100',
        'text-red-600',
        'bg-yellow-100',
        'text-yellow-600',
    ],
    theme: {
        container: {
            center: true,
            padding: '1rem',
        },
        extend: {
            colors: {
                primary: 'var(--color-primary)',
                'primary-foreground': 'var(--color-primary-foreground)',
                secondary: 'var(--color-secondary)',
                'secondary-foreground': 'var(--color-secondary-foreground)',
            },
        },
    },
    darkMode: 'class',
    plugins: [],
};
