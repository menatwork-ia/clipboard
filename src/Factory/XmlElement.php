<?php

/**
 * Created by PhpStorm.
 * User: Stefan Heimes
 * Date: 18.11.2018
 * Time: 14:11
 */

namespace MenAtWork\ClipboardBundle\Factory;

use MenAtWork\ClipboardBundle\Helper\Base;
use MenAtWork\ClipboardBundle\Helper\ContaoBridge;
use MenAtWork\ClipboardBundle\Xml\Element;
use MenAtWork\ClipboardBundle\Xml\Reader;
use MenAtWork\ClipboardBundle\Xml\Writer;

/**
 * Class XmlElement
 *
 * @package MenAtWork\ClipboardBundle\Factory
 */
class XmlElement
{
    /**
     * @var \MenAtWork\ClipboardBundle\Helper\Base
     */
    private $helper;

    /**
     * @var \MenAtWork\ClipboardBundle\Xml\Writer
     */
    private $xmlWriter;

    /**
     * @var \MenAtWork\ClipboardBundle\Xml\Reader
     */
    private $xmlReader;

    /**
     * @var \MenAtWork\ClipboardBundle\Helper\ContaoBridge
     */
    private $contaoBindings;

    /**
     * XmlElement constructor.
     *
     * @param  Base         $helper
     *
     * @param  Writer       $xmlWriter
     *
     * @param  Reader       $xmlReader
     *
     * @param  ContaoBridge $contaoBindings
     */
    public function __construct($helper, $xmlWriter, $xmlReader, $contaoBindings)
    {
        $this->helper         = $helper;
        $this->xmlWriter      = $xmlWriter;
        $this->xmlReader      = $xmlReader;
        $this->contaoBindings = $contaoBindings;
    }

    /**
     * Generate a new xml element.
     *
     * @param string $fileName Name of file.
     *
     * @param string $path     Path to the file.
     *
     * @return Element
     */
    public function getNewXmlElement($fileName, $path)
    {
        $element = new Element($fileName, $path);
        $element
            ->setHelper($this->helper)
            ->setXmlReader($this->xmlReader)
            ->setXmlWriter($this->xmlWriter)
            ->setFiles($this->contaoBindings->getFiles());

        return $element;
    }
}
