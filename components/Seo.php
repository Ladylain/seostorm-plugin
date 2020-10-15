<?php

namespace Arcane\Seo\Components;

use App;
use Config;
use Cms\Components\ViewBag;
use Cms\Classes\ComponentBase;
use Arcane\Seo\Models\Settings;

class Seo extends ComponentBase
{
    public $settings;

    public $disable_schema;

    public $viewBagProperties;

    public function componentDetails()
    {
        return [
            'name'        => 'SEO',
            'description' => 'Renders SEO meta tags in place'
        ];
    }

    public function defineProperties()
    {
        return [
            'disable_schema' => [
                'title' => 'Disable schemas',
                'description' => 'Enable this if you do not want to output schema scripts from the seo component.',
                'type' => 'checkbox'
            ]
        ];
    }

    public function onRun()
    {
        $this->settings = Settings::instance();

        if (!$this->page['viewBag']) {
            $this->page['viewBag'] = new ViewBag();
        }


        if ($this->page->page->hasComponent('blogPost')) {
            $post = $this->page['post'];
            $properties = array_merge(
                $this->page["viewBag"]->getProperties(),
                $post->attributes,
                $post->arcane_seo_options ?: []
            );
            $this->viewBagProperties = $properties;
            $this->page['viewBag']->setProperties($properties);
        } elseif (isset($this->page->apiBag['staticPage'])) {
            $this->viewBagProperties = $this->page['viewBag'] = array_merge(
                $this->page->controller->vars['page']->viewBag,
                $this->page->attributes
            );
        } else {
            $properties = array_merge(
                $this->page['viewBag']->getProperties(), $this->page->settings
            );

            $this->viewBagProperties = $properties;
            $this->page['viewBag']->setProperties($properties);
        }
        $this->disable_schema = $this->property('disable_schema');
    }

    public function getTitle()
    {
        $title = $this->viewBagProperties['title'];
        $localeTitle = $this->getPropertyTranslated('meta_title');
        if ($localeTitle) {
            $title= $localeTitle;
        }

        $settings = Settings::instance();

        if ($settings->site_name_position == 'prefix') {
            $title = "{$settings->site_name} {$settings->site_name_separator} {$title}";
        } else if ($settings->site_name_position == 'suffix') {
            $title = "{$title} {$settings->site_name_separator} {$settings->site_name}";
        }

        return $title;
    }

    public function getDescription()
    {
        $description = Settings::instance()->description;

        $localeDescription = $this->getPropertyTranslated('meta_description');
        if ($localeDescription) {
            $description = $localeDescription;
        }
        return $description;
    }

    public function getOgTitle()
    {
        $ogTitle = $this->getTitle();
        $localeOgTitle = $this->getPropertyTranslated('og_title');
        if ($localeOgTitle) {
            $ogTitle = $localeOgTitle;
        }
        return $ogTitle;
    }

    public function getOgDescription()
    {
        $ogDescription = $this->getDescription();
        $localeOgDescription = $this->getPropertyTranslated('og_description');
        if ($localeOgDescription) {
            $ogDescription = $localeOgDescription;
        }
        return $ogDescription;
    }

    public function getOgImage()
    {
        $mediaUrl = url(Config::get('cms.storage.media.path'));
        $ogImage = $this->getSiteImageFromSettings();
        if ($settingsSiteImage = Settings::instance()->siteImage) {
            $ogImage = $mediaUrl . $settingsSiteImage;
        }

        $localeOgImage = $this->getPropertyTranslated('og_image');
        if ($localeOgImage) {
            $ogImage = $localeOgImage;
        }
        return $ogImage;
    }

    public function getOgVideo()
    {
        $ogVideo = null;
        $localeOgVideo = $this->getPropertyTranslated('og_video');
        if ($localeOgVideo) {
            $ogVideo = $localeOgVideo;
        }
        return $ogVideo;
    }

    public function getOgType()
    {
        return $this->viewBagProperties['og_type'] ?? 'website';;
    }

    public function getSiteImageFromSettings()
    {
        $siteImage = null;
        $mediaUrl = url(Config::get('cms.storage.media.path'));

        if (Settings::instance()->site_image_from === 'media') {
            $siteImage = $mediaUrl . Settings::instance()->site_image;
        }

        if (Settings::instance()->site_image_from === "fileupload") {
            $siteImage = Settings::instance()->site_image_fileupload()->getSimpleValue();
        }

        if (Settings::instance()->site_image_from === "url") {
            $siteImage = Settings::instance()->site_image_url;
        }
        return $siteImage;
    }

    public function getPropertyTranslated(string $viewBagPropertie)
    {
        $locale = App::getLocale();
        $property= null;

        if (isset($this->viewBagProperties[$viewBagPropertie])) {
            $property = $this->viewBagProperties[$viewBagPropertie];
        }

        if (isset($this->viewBagProperties['Locale' . $viewBagPropertie . '[' . $locale . ']'])) {
            $property = $this->viewBagProperties['Locale' . $viewBagPropertie . '['. $locale . ']'];
        }
        return $property;
    }
}
