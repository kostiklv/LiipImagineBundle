<?php

namespace Liip\ImagineBundle\Imagine\Data\PostProcessor;

use Symfony\Component\HttpFoundation\Response;

interface PostProcessorInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    function process(Response $response);
}
