import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * BugHunt VR — "Vault Door" theme.
 *
 * The existing Blade views use the stock Tailwind palette (gray / indigo /
 * emerald ...). Instead of rewriting every class we re-map those palettes to
 * a dark "chamber" look, so every page picks up the theme at once and no
 * markup or logic has to change.
 *   gray    -> inverted (text-gray-900 is now near-white, bg-gray-50 is a dark panel)
 *   indigo  -> cyan   (primary accent)
 *   emerald -> mint   (PASS)   amber -> amber (action / warning)
 *   rose/red -> alert red (FAIL / bug)   blue -> cyan-blue   purple -> violet
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Chakra Petch"', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                chamber: {
                    950: '#05080c',
                    900: '#070c11',
                    800: '#0b1218',
                    700: '#0e1a22',
                    line: '#3fe0c544',
                },
                gray: {
                    50:  '#0b1218',
                    100: '#12262d',
                    200: '#16323a',
                    300: '#2a4650',
                    400: '#6b7c8c',
                    500: '#8b9bab',
                    600: '#b4c0cc',
                    700: '#c9d3dc',
                    800: '#dfe6ec',
                    900: '#e6edf3',
                },
                slate: {
                    50: '#05080c', 100: '#0b1218', 200: '#12262d', 300: '#2a4650',
                    400: '#6b7c8c', 500: '#8b9bab', 600: '#b4c0cc', 700: '#c9d3dc',
                    800: '#dfe6ec', 900: '#e6edf3',
                },
                indigo: {
                    50: '#3fe0c514', 100: '#3fe0c522', 200: '#3fe0c544', 300: '#7ceede',
                    400: '#5fe8d2', 500: '#3fe0c5', 600: '#3fe0c5', 700: '#8ff3e2', 800: '#b4f6ea', 900: '#d6faf3',
                },
                blue: {
                    50: '#38bdf814', 100: '#38bdf822', 200: '#38bdf844', 400: '#7dd3fc',
                    500: '#38bdf8', 600: '#38bdf8', 700: '#7dd3fc', 800: '#bae6fd',
                },
                purple: {
                    50: '#a78bfa14', 100: '#a78bfa22', 200: '#a78bfa44', 400: '#c4b5fd',
                    500: '#a78bfa', 600: '#a78bfa', 700: '#c4b5fd', 800: '#ddd6fe',
                },
                emerald: {
                    50: '#4ade8014', 100: '#4ade8022', 200: '#4ade8044', 400: '#86efac',
                    500: '#4ade80', 600: '#4ade80', 700: '#a3f3c0', 800: '#c9f5d9',
                },
                amber: {
                    50: '#ff8a3d14', 100: '#ff8a3d22', 200: '#ff8a3d44', 400: '#ffa86b',
                    500: '#ff8a3d', 600: '#ff8a3d', 700: '#ffa86b', 800: '#ffc49b',
                },
                rose: {
                    50: '#ff5d5d14', 100: '#ff5d5d22', 200: '#ff5d5d44', 400: '#ff8080',
                    500: '#ff5d5d', 600: '#ff5d5d', 700: '#ff8080', 800: '#ffb0b0',
                },
                red: {
                    50: '#ff5d5d14', 100: '#ff5d5d22', 200: '#ff5d5d44', 400: '#ff8080',
                    500: '#ff5d5d', 600: '#ff5d5d', 700: '#ff8080', 800: '#ffb0b0',
                },
            },
            boxShadow: {
                glow: '0 0 24px #3fe0c555',
                'glow-amber': '0 0 28px #ff8a3d59',
                xs: '0 0 0 1px #3fe0c51a',
            },
        },
    },

    plugins: [forms],
};
