@extends('docs::layout')

@section('page', 'Introduction')
@section('lead', 'The Nyumbani look, as a package: layouts, sign-in and error pages, a modal, toasts and a theme, for apps built on Essentials.')

@section('content')
    <x-docs::section id="what" title="What the UI package is">
        <p><code>nyumbani/ui</code> holds everything an internal Nyumbani app shows on screen. <code>nyumbani/essentials</code> is headless: helpers, services, permissions and the auth backend. The UI package requires Essentials, never the reverse, so an app that needs no Nyumbani look can use Essentials alone.</p>
        <x-docs::table :columns="['Included', 'Details']" :rows="[
            ['Layouts', 'The portal, auth, error and base layouts, with the sidebar and navbar.'],
            ['Sign-in pages', 'Login, forgot password and reset password, bound to Essentials\' AuthViews contract.'],
            ['Components', 'The modal, the period picker, the loader, the file input and the navigation links, all as x-nyumbani components.'],
            ['Scripts', 'Toasts, the shell, the double-submit guard, the file input and the modal, pre-bundled into one file.'],
            ['Theme', 'A Tailwind 4 theme with the buttons, forms and colours of every app.'],
            ['Error pages', 'The 401, 403, 404, 419, 429, 500 and 503 pages.'],
            ['Branding', 'Favicons, the web manifest and the images, with the theme colour and sign-in sentence in config/ui.php.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="other-projects" title="Beyond Nyumbani apps">
        <p>The package can also serve your own projects: import <code>base.css</code> for the generic styles, define your own colours, turn the error pages off and write your own layouts. See <x-docs::package-link package="nyumbani-ui" page="installation" anchor="other-projects">Using the UI in a project that is not a Nyumbani app</x-docs::package-link>.</p>
    </x-docs::section>

    <x-docs::section id="namespace" title="One namespace">
        <p>Views and components share the <code>nyumbani</code> namespace: <code>nyumbani::layouts.portal</code>, <code>nyumbani::auth.login</code> and <code>&lt;x-nyumbani::modal&gt;</code>.</p>
    </x-docs::section>

    <x-docs::section id="config" title="Configuration">
        <p>Publish it with the <code>ui-config</code> tag.</p>
        <x-docs::table :columns="['Key', 'Default', 'Effect']" :rows="[
            ['branding.description', 'null', 'The sentence under the app name on the sign-in page. Set with UI_DESCRIPTION.'],
            ['branding.theme_color', '#d97706', 'The browser address bar and installed app colour. Set with UI_THEME_COLOR.'],
            ['loader', 'null', 'The image the loader shows, a path under public/ or a full URL. Empty uses the Nyumbani loader. Set with UI_LOADER.'],
            ['loader_message', 'null', 'The text under the loader when a page passes no message. Empty uses "Nyumbani is processing your data...". Set with UI_LOADER_MESSAGE.'],
            ['error_pages', 'true', 'The package error pages replace the framework ones. Set to false to leave them alone.'],
        ]" />
    </x-docs::section>
@endsection
