<?php

namespace MenAtWork\ClipboardBundle;

use MenAtWork\ClipboardBundle\DependencyInjection\ClipboardExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ClipboardBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new ClipboardExtension();
    }
}
