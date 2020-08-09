<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Phoenix\Html\HtmlHeaderManager;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\View\Helper\AbstractHelper;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Core\View\Traits\LayoutRenderableTrait;
use Windwalker\Data\Data;
use Windwalker\Renderer\AbstractRenderer;

/**
 * The AbstractHtmlView class.
 *
 * @property-read  HtmlHeaderManager head
 * @property-read  string name
 * @property-read  string layout
 *
 * @since  3.0
 */
class HtmlView extends AbstractView implements LayoutRenderableInterface
{
    use LayoutRenderableTrait;

    /**
     * Property helperSet.
     *
     * @var  HelperSet
     */
    protected $helperSet;

    /**
     * Method to instantiate the view.
     *
     * @param   array                   $data     The data array.
     * @param   array                   $config   The view config.
     * @param   string|AbstractRenderer $renderer The renderer engine.
     */
    public function __construct(array $data = null, $config = null, $renderer = null)
    {
        $this->renderer = $renderer ?: $this->renderer;
        $this->renderer = $this->renderer ?: RendererHelper::PHP;

        parent::__construct($data, $config);

        $this->data = new Data($this->data);
    }

    /**
     * boot
     *
     * @return  void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->setRenderer($this->renderer);

        $this->booted = true;
    }

    /**
     * Method to escape output.
     *
     * @param   string $output The output to escape.
     *
     * @return  string  The escaped output.
     *
     * @since   2.0
     */
    public function escape($output)
    {
        // Escape the output.
        return $this->getRenderer()->escape($output);
    }

    /**
     * render
     *
     * @return  string
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        $this->boot();

        $this->registerPaths();

        return parent::render();
    }

    /**
     * addHelper
     *
     * @param   string                $name
     * @param   object|AbstractHelper $helper
     *
     * @return  static
     */
    public function addHelper($name, $helper)
    {
        $this->getHelperSet()->addHelper($name, $helper);

        return $this;
    }

    /**
     * Method to get property HelperSet
     *
     * @return  HelperSet
     */
    public function getHelperSet()
    {
        if (!$this->helperSet) {
            $this->helperSet = new HelperSet($this);
        }

        return $this->helperSet;
    }

    /**
     * Method to set property helperSet
     *
     * @param   HelperSet $helperSet
     *
     * @return  static  Return self to support chaining.
     */
    public function setHelperSet($helperSet)
    {
        $this->helperSet = $helperSet;

        return $this;
    }

    /**
     * doRender
     *
     * @param  Data $data
     *
     * @return string
     */
    protected function doRender($data)
    {
        return $this->renderer->render($this->getLayout(), (array) $data);
    }

    /**
     * prepareGlobals
     *
     * @param \Windwalker\Data\Data $data
     *
     * @return  void
     */
    protected function prepareGlobals($data)
    {
        $name = strtolower($this->name);
        $data->view   = $this;
        $data->helper = $this->getHelperSet();
        $data->bodyClass = "package-{$this->getPackage()->name} view-{$name} layout-{$this->getLayout()}";

        foreach ($this->getRendererManager()->getHelpers() as $name => $helper) {
            $data->helper->addHelper($name, $helper);
        }

        $globals            = $this->getRendererManager()->getGlobals();
        $globals['package'] = $this->getPackage();
        $globals['router']  = (clone $this->getPackage()->router)->mute(true);

        $data->bind($globals);
    }

    /**
     * __get
     *
     * @param string $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        if ($name === 'head') {
            return $this->package->container->get('html.header');
        }

        if ($name === 'name') {
            return strtolower($this->name);
        }

        if ($name === 'layout') {
            return $this->layout;
        }

        return parent::__get($name);
    }
}
