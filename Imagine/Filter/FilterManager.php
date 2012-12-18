<?php

namespace Liip\ImagineBundle\Imagine\Filter;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use Liip\ImagineBundle\Imagine\Data\PostProcessor\PostProcessorInterface;

class FilterManager
{
    /**
     * @var FilterConfiguration
     */
    private $filterConfig;

    /**
     * @var array
     */
    private $loaders = array();

    /**
     * @var array
     */
    private $postProcessors = array();

    /**
     * @param FilterConfiguration $filterConfig
     */
    public function __construct(FilterConfiguration $filterConfig)
    {
        $this->filterConfig = $filterConfig;
    }

    /**
     * @param string $filter
     * @param LoaderInterface $loader
     * 
     * @return void
     */
    public function addLoader($filter, LoaderInterface $loader)
    {
        $this->loaders[$filter] = $loader;
    }

    /**
     * @param                        $name
     * @param PostProcessorInterface $postProcessor
     */
    public function addPostProcessor($name, PostProcessorInterface $postProcessor)
    {
        $this->postProcessors[$name] = $postProcessor;
    }

    /**
     * @return FilterConfiguration
     */
    public function getFilterConfiguration()
    {
        return $this->filterConfig;
    }

    /**
     * @param Request $request
     * @param string $filter
     * @param Imagine\Image\ImageInterface $image
     * @param string $localPath
     *
     * @return Response
     */
    public function get(Request $request, $filterSet, $image, $localPath)
    {
        $config = $this->filterConfig->get($filterSet);

        foreach ($config['filters'] as $filter => $options) {
            if (!isset($this->loaders[$filter])) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find filter loader for "%s" filter type', $filter
                ));
            }
            $image = $this->loaders[$filter]->load($image, $options);
        }

        if (empty($config['format'])) {
            $format = pathinfo($localPath, PATHINFO_EXTENSION);
            $format = $format ?: 'png';
        } else {
            $format = $config['format'];
        }

        $quality = empty($config['quality']) ? 100 : $config['quality'];

        $image = $image->get($format, array('quality' => $quality));

        $contentType = $request->getMimeType($format);
        if (empty($contentType)) {
            $contentType = 'image/'.$format;
        }

        $response = new Response($image, 200, array('Content-Type' => $contentType));

        foreach ($config['post_processors'] as $postProcessorName) {
            if (!isset($this->postProcessors[$postProcessorName])) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not find post processor "%s" while processing filter set "%s"', $postProcessorName, $filterSet
                ));
            }
            $response = $this->postProcessors[$postProcessorName]->process($response);
        }

        return $response;
    }
}
