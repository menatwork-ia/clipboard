<?php

namespace MenAtWork\ClipboardBundle\Contao\Compat;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

/**
 * This class creates instances of Contao classes that have parent classes in global namespace (All in Contao <=4.4).
 *
 * To instantiate, we ensure the framework is booted prior usage.
 */
class ContaoFactory
{
    /**
     * The Contao framework.
     *
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Create a new instance.
     *
     * @param ContaoFrameworkInterface $framework The Contao framework.
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Create an adapter.
     *
     * @param string $className The class name to create an adapter for.
     *
     * @return Adapter
     */
    public function getAdapter($className)
    {
        $this->framework->initialize();

        return $this->framework->getAdapter($className);
    }

    /**
     * Create an instance.
     *
     * @param string $className The class name to create an instance for.
     *
     * @return object
     */
    public function createInstance($className)
    {
        $this->framework->initialize();

        return $this->framework->createInstance($className);
    }
}
