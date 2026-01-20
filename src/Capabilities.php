<?php

namespace Plausible\Analytics\WP;

/**
 * This class behaves like an enum, while we can't really support yet, since WP hasn't dropped support yet for PHP 8.0 and lower.
 *
 * @codeCoverageIgnore
 */
final class Capabilities {

	const FUNNELS = 'funnels';

	const GOALS = 'goals';

	const PROPS = 'props';

	const REVENUE = 'revenue';

	const STATS = 'stats';
}
