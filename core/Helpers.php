<?php

class Helpers
{
    public static function redirect(string $path): never
    {
        $url = APP_BASE . '/index.php?url=' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    public static function url(string $path): string
    {
        return APP_BASE . '/index.php?url=' . ltrim($path, '/');
    }

    /**
     * URL for a file under public/, with a filemtime-based cache-busting
     * query string appended. app.css has no build step and Apache serves it
     * with only Last-Modified/ETag (no explicit Cache-Control), so browsers
     * can and do reuse a stale cached copy across normal navigations — a
     * hard refresh doesn't reliably fix this either. Appending ?v=<mtime>
     * changes the URL itself on every edit, which forces a fresh fetch
     * regardless of what the browser cached.
     */
    public static function assetUrl(string $path): string
    {
        $path    = '/' . ltrim($path, '/');
        $fsPath  = __DIR__ . '/../public' . $path;
        $version = file_exists($fsPath) ? filemtime($fsPath) : time();
        return APP_BASE . '/public' . $path . '?v=' . $version;
    }

    /**
     * Translate a date-range preset key into a 'Y-m-d' lower bound, or ''
     * for "all time". Used by the Reservations and Trips list filters so
     * both pages agree on what "this week" means.
     */
    public static function dateRangeFloor(string $preset): string
    {
        return match ($preset) {
            'today'  => date('Y-m-d'),
            'week'   => date('Y-m-d', strtotime('monday this week')),
            'month'  => date('Y-m-01'),
            'last30' => date('Y-m-d', strtotime('-30 days')),
            default  => '',
        };
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Cut a string to a fixed character count with a trailing ellipsis so a
     * single long value (e.g. a generated page title) can't blow out a
     * fixed-width UI element.
     */
    public static function truncate(string $value, int $limit): string
    {
        $value = trim($value);
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit) . '…';
    }

    /**
     * Classify a trip's GPS health from the age (in seconds) of its newest
     * gps_tracking_logs row, against the GPS_*_MAX_SECONDS thresholds in
     * config/constants.php. $ageSeconds must come from MySQL's own NOW()
     * (e.g. GpsTrackingLogModel::getLastPointWithElapsed()), never from
     * PHP's time() — the DB session is pinned to +08:00 while PHP's default
     * timezone is not (see config/database.php).
     *
     * Used by TripController::liveMap() for the initial render and by
     * GpsController::feed() on every poll, so both agree on the same tier
     * for the same age.
     *
     * @return array{key: string, label: string, badge: string}
     */
    public static function gpsStatus(?int $ageSeconds, string $tripStatus): array
    {
        if (in_array($tripStatus, TRIP_TERMINAL_STATUSES, true)) {
            $key = GPS_STATUS_ENDED;
        } elseif ($ageSeconds === null) {
            $key = GPS_STATUS_AWAITING;
        } elseif ($ageSeconds <= GPS_LIVE_MAX_SECONDS) {
            $key = GPS_STATUS_LIVE;
        } elseif ($ageSeconds <= GPS_DELAYED_MAX_SECONDS) {
            $key = GPS_STATUS_DELAYED;
        } elseif ($ageSeconds <= GPS_STALE_MAX_SECONDS) {
            $key = GPS_STATUS_STALE;
        } else {
            $key = GPS_STATUS_NO_SIGNAL;
        }

        return [
            'key'   => $key,
            'label' => GPS_STATUS_LABELS[$key],
            'badge' => GPS_STATUS_BADGES[$key],
        ];
    }

    /**
     * Build LIMIT/OFFSET values and page-link markup for a paginated list.
     *
     * $baseQuery is the query string to reuse on every page link — everything
     * that should survive across pages (at minimum 'url', plus any active
     * filters), built by the caller with e.g.
     * http_build_query(['url' => 'reports/trip-history', ...$filters]).
     * 'page' must not already be in it; this method appends it per link.
     *
     * @return array{limit:int, offset:int, page:int, total_pages:int, html:string}
     */
    public static function paginate(int $total, int $page, int $perPage, string $baseQuery): array
    {
        $perPage    = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $html = '';
        if ($totalPages > 1) {
            $link = fn(int $p): string => self::e(APP_BASE . '/index.php?' . $baseQuery . '&page=' . $p);

            $html .= '<div class="pagination">';
            $html .= '<div class="pagination-info">Page ' . $page . ' of ' . $totalPages
                    . ' (' . number_format($total) . ' total)</div>';
            $html .= '<div class="pagination-links">';
            $html .= $page > 1
                ? '<a href="' . $link($page - 1) . '">&laquo; Prev</a>'
                : '<span class="disabled">&laquo; Prev</span>';

            // First/last page plus a window around the current page, with an
            // ellipsis for any gap — keeps the strip short no matter how many
            // pages the underlying report grows to.
            $shown = [];
            for ($p = 1; $p <= $totalPages; $p++) {
                if ($p === 1 || $p === $totalPages || abs($p - $page) <= 2) {
                    $shown[] = $p;
                }
            }

            $prev = null;
            foreach ($shown as $p) {
                if ($prev !== null && $p - $prev > 1) {
                    $html .= '<span class="disabled">&hellip;</span>';
                }
                $html .= $p === $page
                    ? '<span class="active">' . $p . '</span>'
                    : '<a href="' . $link($p) . '">' . $p . '</a>';
                $prev = $p;
            }

            $html .= $page < $totalPages
                ? '<a href="' . $link($page + 1) . '">Next &raquo;</a>'
                : '<span class="disabled">Next &raquo;</span>';
            $html .= '</div></div>';
        }

        return [
            'limit'       => $perPage,
            'offset'      => $offset,
            'page'        => $page,
            'total_pages' => $totalPages,
            'html'        => $html,
        ];
    }
}
