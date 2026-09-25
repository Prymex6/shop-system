import js from '@eslint/js'
import ts from 'typescript-eslint'
import vue from 'eslint-plugin-vue'
import prettier from 'eslint-config-prettier'
import globals from 'globals'

export default [
  {
    ignores: [
      'public/build/**',
      'vendor/**',
      'node_modules/**',
      'storage/**',
      'tests/e2e/.output/**',
      'tests/e2e/.report/**',
    ],
  },

  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  prettier,

  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        // Ziggy puts route() on the window; Laravel Echo and Pusher
        // are loaded the same way from bootstrap.js.
        route: 'readonly',
        Echo: 'readonly',
        Pusher: 'readonly',
        // Google Tag Manager's queue, created by the tag it injects.
        dataLayer: 'readonly',
      },
    },
    rules: {
      // The manager panel has pages whose markup is genuinely deeper
      // than three levels of component. Enforcing a limit there would
      // mean splitting files to satisfy a linter rather than a reader.
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/html-indent': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/attributes-order': 'off',
      'vue/first-attribute-linebreak': 'off',

      // Laravel's paginator hands back labels that are markup —
      // "&laquo; Previous" and the like — and every list in this project
      // renders them through Inertia's <Link>. The content comes from
      // the framework, never from a person, so the rule reports rather
      // than blocks: a v-html that is not the paginator still shows up
      // in the output and should be looked at.
      'vue/no-v-text-v-html-on-component': 'warn',

      // Inertia names a page by its path, so every page component is
      // called Index, Show or Form. The rule exists to stop a component
      // colliding with an HTML element, which a path-named page cannot.
      'vue/multi-word-component-names': 'off',

      // Reported rather than hidden: an unused variable is usually a
      // leftover, and seeing them is the point. It is a warning so the
      // build is not blocked by something nobody is looking at yet.
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },

  {
    // The hand-written service workers run in a worker, where `clients`
    // and `self` exist and `window` does not.
    files: ['public/sw.js', 'public/sw-push.js'],
    languageOptions: {
      globals: { ...globals.serviceworker },
    },
  },

  {
    // Build configuration, the maintenance scripts and the E2E suite run
    // in Node, not a browser.
    files: ['*.config.js', '*.config.ts', 'tools/**/*.mjs', 'tests/e2e/**/*.ts', 'tests/e2e/**/*.js'],
    languageOptions: {
      globals: { ...globals.node },
    },
    rules: {
      '@typescript-eslint/no-explicit-any': 'warn',
    },
  },
]
