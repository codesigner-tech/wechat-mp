<?php

namespace Codesigner\WechatMp;

use Illuminate\Support\ServiceProvider;

class WechatMpServiceProvider extends ServiceProvider {
    protected $defer = true;

	public function boot() {

		$this->publishes([
			__DIR__.'/../config/wechatMp.php' => config_path('wechatMp.php'),
		], 'wechatMp');
	}

    public function register() {
		$this->mergeConfigFrom( __DIR__.'/../config/wechatMp.php', 'wechatMp');
        $this->app->singleton('wechatMp', function($app) {
            $config = $app->make('config');
            return new WechatMp();
        });
    }

    public function provides() {
        return ['wechatMp'];
    }
}