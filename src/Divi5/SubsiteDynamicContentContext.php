<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi\Divi5;

use WP_Post;

/** Provides the validated current subsite context to Divi dynamic sources. */
final class SubsiteDynamicContentContext
{
	public static function current(): ?WP_Post
	{
		$subsite = function_exists('servinder_subsites_current') ? servinder_subsites_current() : null;
		if (!$subsite instanceof WP_Post || 'servinder_subsite' !== get_post_type($subsite)) {
			return null;
		}
		if ('publish' !== $subsite->post_status && !apply_filters('servinder_subsites_is_preview', false)) {
			return null;
		}

		return $subsite;
	}
}
