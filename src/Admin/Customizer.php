<?php
namespace VotingPhoto\Admin;

use  VotingPhoto\Admin\Customizer\CustomizeDonate;
/**
 * Class Customizer
 *
 * @package ResponsiveTable\Admin
 */



class Customizer
{


    public function __construct()
    {

        $this->registerHooks();
    }

    public function registerHooks(){
        add_action('customize_register', array($this, 'addSection'));
        add_action('customize_register', array($this, 'addSettings'));
    }

    public function addSection($wp_customize){


        $wp_customize->add_section( 'gallery_settings' , array(
            'title'      => __('Gallery Settings', 'voting-for-photo'),
            'priority'   => 200,
        ) );

    }


    public function addSettings($wp_customize){


        $radioOptions = array('choices' => array(
            'heart_red' => __('Heart red', 'voting-for-photo'),
            'heart_white' => __('Heart white', 'voting-for-photo'),
            'like_red' => __('Like red', 'voting-for-photo'),
            'like_white' => __('Like white', 'voting-for-photo')
        ),
            'default' => 'heart_red',
        );

        $this->addSetting('like_icon_type', 'select', 'Vote icon type', $wp_customize, $radioOptions);


        $checkboxOptions = array(
            'default' => 0
        );

        $this->addSetting('adaptive_gallery', 'checkbox', 'Add responsive gallery styles', $wp_customize, $checkboxOptions);


        $wp_customize->add_setting('vff_donate', array(

        ));


        $wp_customize->add_control(new CustomizeDonate($wp_customize, 'vff_donate', array(
            'label' => __('Donate to this plugin', 'voting-for-photo'),
            'section' => 'gallery_settings',
            'settings' => 'vff_donate',
        )));

    }


    private function addSetting($name, $type, $label, $wp_customize, $args = array())
    {

        $default = array(
            'default' => '',
            'description' => ''
        );
        $args = array_merge($default, $args);

        switch ($type) {
            case 'text':
            case 'checkbox':
                $wp_customize->add_setting($name, array(
                    'default' => $args['default']
                ));
                $wp_customize->selective_refresh->add_partial($name, array(
                    'selector' => '.' . $name
                ));
                $wp_customize->add_control($name, array(
                        'label' => __($label, 'voting-for-photo'),
                        'section' => 'gallery_settings',
                        'type' => $type,
                    )
                );
                break;

            case 'select':
                $wp_customize->add_setting($name, array(
                    'capability' => 'edit_theme_options',
                    'default' => $args['default'],
                ));
                $wp_customize->selective_refresh->add_partial($name, array(
                    'selector' => '.' . $name
                ));

                $wp_customize->add_control($name, array(
                    'type' => 'select',
                    'section' => 'gallery_settings',
                    'label' => __($label, 'voting-for-photo'),
                    'description' => __($args['description'], 'voting-for-photo'),
                    'choices' => $args['choices'],
                ));
                break;

            case 'radio':
                $wp_customize->add_setting($name, array(
                    'capability' => 'edit_theme_options',
                    'default' => $args['default'],
                ));
                $wp_customize->selective_refresh->add_partial($name, array(
                    'selector' => '.'.$name
                ));

                $wp_customize->add_control($name, array(
                    'type' => 'radio',
                    'section' => 'gallery_settings',
                    'label' => __($label, 'wp-question-answer'),
                    'description' => __($args['description'], 'voting-for-photo'),
                    'choices' => $args['choices'],
                ));
                break;

            case 'range':
                $wp_customize->add_setting($name, array(
                    'default' => $args['default']
                ));
                $wp_customize->selective_refresh->add_partial($name, array(
                    'selector' => '.'.$name
                ));

                $wp_customize->add_control(new CustomizeRange($wp_customize, $name, array(
                    'label' => __($label, 'voting-for-photo'),
                    'min' => $args['min'],
                    'max' => $args['max'],
                    'step' => $args['step'],
                    'section' => 'gallery_settings',
                )));

                break;

            case 'color':
                $wp_customize->add_setting($name, array(
                    'default' => $args['default']
                ));
                $wp_customize->selective_refresh->add_partial($name, array(
                    'selector' => '.'.$name
                ));

                $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $name, array(
                    'label' => __($label, 'voting-for-photo'),
                    'section' => 'gallery_settings',
                    'settings' => $name,
                )));


                break;
        }

    }







}