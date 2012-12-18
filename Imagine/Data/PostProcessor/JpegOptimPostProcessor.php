<?php

namespace Liip\ImagineBundle\Imagine\Data\PostProcessor;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class JpegOptimPostProcessor implements PostProcessorInterface
{
    /** @var string Path to jpegoptim binary */
    private $jpegoptimBin;

    /**
     * Constructor.
     *
     * @param string $jpegoptimBin Path to the jpegoptim binary
     */
    public function __construct($jpegoptimBin = '/usr/local/bin/jpegoptim')
    {
        $this->jpegoptimBin = $jpegoptimBin;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     * s
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @see Implementation taken from Assetic\Filter\JpegoptimFilter
     */
    function process(Response $response)
    {
        $type = $response->headers->get('Content-Type');
        if ('image/jpeg' != $type) {
            throw new \RuntimeException(sprintf('Could not apply jpegoptim post-processor to "%s" content type', $type));
        }

        $pb = new ProcessBuilder(array($this->jpegoptimBin));

//        if ($this->stripAll) {
            $pb->add('--strip-all');
//        }

//        if ($this->max) {
//            $pb->add('--max='.$this->max);
//        }

        $pb->add($input = tempnam(sys_get_temp_dir(), 'imagine_jpegoptim'));
        file_put_contents($input, $response->getContent());

        $proc = $pb->getProcess();
        $proc->run();

        if (false !== strpos($proc->getOutput(), 'ERROR') || 0 !== $proc->getExitCode()) {
            unlink($input);
            throw new ProcessFailedException($proc);
        }

        $response->setContent(file_get_contents($input));

        unlink($input);

        return $response;
    }
}