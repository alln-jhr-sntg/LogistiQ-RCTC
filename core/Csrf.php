<?php

/**
 * Centralized CSRF protection (synchronizer token pattern).
 *
 * One token per session, minted lazily on first use and carried across
 * session_regenerate_id() calls (PHP keeps $_SESSION content across a
 * regenerate — only the session id changes). Auth::login() calls
 * rotate() explicitly so a token seen by an anonymous visitor is never
 * reusable after that visitor authenticates.
 *
 * index.php calls verify() once, centrally, for every POST request
 * before any route is dispatched — individual controllers never touch
 * this class directly. Views call field() to embed the hidden input.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    public  const FIELD       = '_csrf';

    // Mint-once-per-session. random_bytes(32) -> 64 hex chars.
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    // Hidden input for a <form method="POST">. Escaped like any other
    // dynamic attribute value, via Helpers::e().
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . Helpers::e(self::token()) . '">';
    }

    // Timing-safe compare against the session's own copy. A missing
    // session token (never minted, or session died) always fails --
    // there is no empty-vs-empty pass-through.
    public static function check(): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        $given    = $_POST[self::FIELD] ?? '';
        return $expected !== '' && $given !== '' && hash_equals($expected, $given);
    }

    // Enforce; never returns on failure. Split by session state:
    // an expired/anonymous session is the overwhelmingly common cause
    // (a form left open past session lifetime) and gets a friendly
    // re-login prompt. A logged-in session with a wrong token is a
    // real forged-request signal and gets a hard 403.
    public static function verify(): void
    {
        if (self::check()) {
            return;
        }

        error_log(sprintf(
            '[LVMS] CSRF check failed: user_id=%s url=%s',
            Auth::id() ?? 'anon',
            $_GET['url'] ?? ''
        ));

        if (!Auth::check()) {
            Helpers::setFlash('error', 'Your session expired. Please sign in again.');
            Helpers::redirect('/login');
        }

        render_error_page(403, 'Forbidden', 'This request could not be verified and was blocked. Please refresh the page and try again.', showLink: true);
        exit;
    }

    // Drop the current token so the next token()/field() call mints a
    // fresh one. Called by Auth::login() right after
    // session_regenerate_id() so a pre-auth token can't carry over.
    public static function rotate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
