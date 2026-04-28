# Follow-up patches

Two fixes on top of `feature/harden-proxy-api`:

1. All rejections return `404 rest_no_route` (stops endpoint discovery via error codes).
2. `Origin`/`Referer` checks now require a real host (stops `Origin: /` bypass).

---

## `src/Proxy.php`

```diff
 public function validate_proxy_request( $request ) {
     $max_request_bytes = (int) apply_filters( 'plausible_analytics_proxy_max_body_bytes', self::MAX_REQUEST_BYTES );
     $raw_body          = (string) $request->get_body();

     if ( $max_request_bytes > 0 && strlen( $raw_body ) > $max_request_bytes ) {
-        return new WP_Error( 'plausible_proxy_body_too_large', __( 'Proxy request body too large.', 'plausible-analytics' ), [ 'status' => 413 ] );
+        return $this->rest_no_route();
     }

     if ( ! $this->has_json_content_type() ) {
-        return new WP_Error( 'plausible_proxy_invalid_content_type', __( 'Proxy request must be sent as JSON.', 'plausible-analytics' ), [ 'status' => 400 ] );
+        return $this->rest_no_route();
     }

     $params = $request->get_json_params();

     if ( ! is_array( $params ) ) {
-        return new WP_Error( 'plausible_proxy_invalid_json', __( 'Proxy request payload must be valid JSON.', 'plausible-analytics' ), [ 'status' => 400 ] );
+        return $this->rest_no_route();
     }

     if ( ! $this->has_valid_provenance() ) {
-        return new WP_Error( 'rest_no_route', __( 'No route was found matching the URL and request method.', 'plausible-analytics' ), [ 'status' => 404 ] );
+        return $this->rest_no_route();
     }

     if ( ! $this->has_valid_payload( $params ) ) {
-        return new WP_Error( 'plausible_proxy_invalid_payload', __( 'Proxy request payload is invalid.', 'plausible-analytics' ), [ 'status' => 400 ] );
+        return $this->rest_no_route();
     }

     return true;
 }

+private function rest_no_route() {
+    return new WP_Error(
+        'rest_no_route',
+        __( 'No route was found matching the URL and request method.', 'plausible-analytics' ),
+        [ 'status' => 404 ]
+    );
+}

 private function has_valid_provenance() {
     ...
-    if ( $origin && $this->url_matches_home_host( $origin ) ) {
+    if ( $origin && $this->host_matches_home( $origin ) ) {
         return true;
     }

-    if ( $referer && $this->url_matches_home_host( $referer ) ) {
+    if ( $referer && $this->host_matches_home( $referer ) ) {
         return true;
     }

     return false;
 }

+private function host_matches_home( $url ) {
+    $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
+    if ( ! $home_host ) {
+        return false;
+    }
+    $host = wp_parse_url( $url, PHP_URL_HOST );
+    if ( ! $host ) {
+        return false;
+    }
+    return $this->normalize_domain( $host ) === $this->normalize_domain( $home_host );
+}
```

---

## `mu-plugin/plausible-proxy-speed-module.php`

```diff
 private function maybe_short_circuit_request() {
     if ( ! $this->is_proxy_request ) {
         return;
     }

     if ( $this->is_namespace_index_request() || ! $this->is_exact_proxy_endpoint_request() ) {
-        $this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
+        $this->send_rest_no_route();
     }

     if ( $this->get_request_method() !== 'POST' ) {
-        $this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
+        $this->send_rest_no_route();
     }

     if ( ! $this->has_json_content_type() ) {
-        $this->send_json_error( 400, 'plausible_proxy_invalid_content_type', 'Proxy request must be sent as JSON.' );
+        $this->send_rest_no_route();
     }

     if ( ! $this->has_valid_provenance() ) {
-        $this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
+        $this->send_rest_no_route();
     }

     if ( $this->request_body_too_large() ) {
-        $this->send_json_error( 413, 'plausible_proxy_body_too_large', 'Proxy request body too large.' );
+        $this->send_rest_no_route();
     }

     if ( ! $this->has_valid_payload() ) {
-        $this->send_json_error( 400, 'plausible_proxy_invalid_payload', 'Proxy request payload is invalid.' );
+        $this->send_rest_no_route();
     }
 }

+private function send_rest_no_route() {
+    $this->send_json_error( 404, 'rest_no_route', 'No route was found matching the URL and request method.' );
+}

 private function has_valid_provenance() {
     ...
-    if ( $origin && $this->url_matches_home_host( $origin ) ) {
+    if ( $origin && $this->host_matches_home( $origin ) ) {
         return true;
     }

-    if ( $referer && $this->url_matches_home_host( $referer ) ) {
+    if ( $referer && $this->host_matches_home( $referer ) ) {
         return true;
     }

     return false;
 }

+private function host_matches_home( $url ) {
+    $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
+    if ( ! $home_host ) {
+        return false;
+    }
+    $host = wp_parse_url( $url, PHP_URL_HOST );
+    if ( ! $host ) {
+        return false;
+    }
+    return $this->normalize_domain( $home_host ) === $this->normalize_domain( $host );
+}
```
