<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes non-resource objects.
 *
 * It decorates the output with JSON HAL metadata when appropriate, but otherwise
 * just passes through to the decorated normalizer.
 */
final class NonResourceObjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'jsonhal';

    private $decorated;
    private $iriConverter;

    public function __construct(NormalizerInterface $decorated, IriConverterInterface $iriConverter)
    {
        $this->decorated = $decorated;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (isset($context['api_resource'])) {
            $originalResource = $context['api_resource'];
            unset($context['api_resource']);
        }

        $data = $this->decorated->normalize($object, $format, $context);
        if (!\is_array($data)) {
            return $data;
        }

        if (!isset($originalResource)) {
            return $data;
        }

        $metadata = [
            '_links' => [
                'self' => [
                    'href' => $this->iriConverter->getIriFromItem($originalResource),
                ],
            ],
        ];

        return $metadata + $data;
    }
}
