<?php

defined('ABSPATH') || exit;

class DTS_Shipping_Method extends WC_Shipping_Method
{
    /** @var string */
    public $cost = '0';

    public function __construct(int $instance_id = 0)
    {
        $this->id = 'dts_custom_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('DTS Delivery', 'dts-custom-shipping');
        $this->method_description = __('Delivery with city, branch and comment.', 'dts-custom-shipping');
        $this->supports = ['shipping-zones', 'instance-settings'];

        $this->init();
    }

    public function init(): void
    {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', __('Custom delivery', 'dts-custom-shipping'));
        $this->enabled = $this->get_option('enabled', 'yes');
        $this->cost = $this->get_option('cost', '0');

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Title', 'dts-custom-shipping'),
                'type' => 'text',
                'default' => __('Custom delivery', 'dts-custom-shipping'),
            ],
            'cost' => [
                'title' => __('Cost', 'dts-custom-shipping'),
                'type' => 'number',
                'default' => '0',
                'custom_attributes' => ['step' => '0.01', 'min' => '0'],
            ],
            'enabled' => [
                'title' => __('Enabled', 'dts-custom-shipping'),
                'type' => 'checkbox',
                'default' => 'yes',
            ],
        ];
    }

    public function calculate_shipping($package = []): void
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => wc_format_decimal($this->cost),
            'package' => $package,
        ];

        $this->add_rate($rate);
    }
}
