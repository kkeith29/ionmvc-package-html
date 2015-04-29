<?php

namespace ionmvc\packages;

use ionmvc\classes\app;
use ionmvc\classes\hook;
use ionmvc\classes\response;

class html extends \ionmvc\classes\package {

	const version = '1.0.0';

	public function setup() {
		app::hook()->attach('response.create',function() {
			response::hook()->add('html_header',[
				'position' => hook::position_before,
				'hook'     => 'view_output'
			]);
			response::hook()->add('html_footer',[
				'position' => hook::position_after,
				'hook'     => 'view_output'
			]);
		});
	}

	public static function package_info() {
		return [
			'author'      => 'Kyle Keith',
			'version'     => self::version,
			'description' => 'HTML handler'
		];
	}

}

?>