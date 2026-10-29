<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articlefooter
 *
 * @copyright   (C) 2026 Magnum <https://www.magnumblog.space>
 * @license     GNU General Public License version 3 or later; see LICENSE
 *
 * Developed by Magnum https://www.magnumblog.space
 * When copying or modifying, keep this copyright notice.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Magnum\\Module\\ArticleFooter'));
        $container->registerServiceProvider(new HelperFactory('\\Magnum\\Module\\ArticleFooter\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
