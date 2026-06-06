<?php
/**
 * Tiny stand-in for an external "domain authority" API, used only by the local demo.
 *
 * Returns {"score": <0-100>} for a given ?domain=, with a deliberate ~250ms delay so the
 * plugin's "call the API once per row, every page load, with no caching" problem is easy to
 * feel. Six partner rows => ~1.5s of avoidable latency on every render.
 *
 * Not part of the exercise; candidates don't need to touch this.
 */

usleep( 250000 ); // 250ms of simulated network latency.

$domain = isset( $_GET['domain'] ) ? (string) $_GET['domain'] : '';

// Deterministic pseudo-score derived from the domain, so output is stable across reloads.
$score = $domain === '' ? 0 : ( ( crc32( $domain ) % 41 ) + 60 ); // 60–100

header( 'Content-Type: application/json' );
echo json_encode( array(
	'domain' => $domain,
	'score'  => $score,
) );
