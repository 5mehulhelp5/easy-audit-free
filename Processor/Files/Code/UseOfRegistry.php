<?php

namespace Crealoz\EasyAudit\Processor\Files\Code;

use Crealoz\EasyAudit\Api\Processor\Audit\FileProcessorInterface;
use Crealoz\EasyAudit\Exception\Processor\Getters\NotAClassException;
use Crealoz\EasyAudit\Processor\Files\AbstractFileProcessor;
use Crealoz\EasyAudit\Service\Classes\ConstructorService;
use Crealoz\EasyAudit\Service\FileSystem\ClassNameGetter;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\ObjectManager\DefinitionInterface;

class UseOfRegistry extends AbstractFileProcessor implements FileProcessorInterface
{


    public function __construct(
        private readonly ClassNameGetter $classNameGetter,
        private readonly DefinitionInterface $definitions,
        private readonly ConstructorService $constructorService
    )
    {
    }

    public function prepopulateResults(): void
    {
        parent::prepopulateResults();
        $this->results = [
            'hasErrors' => false,
            'errors' => [
                'useOfRegistry' => $this->getUseOfRegistryEntry(),
            ],
            'warnings' => [],
            'suggestions' => []
        ];
    }

    private function getUseOfRegistryEntry(): array
    {
        $title = __('Use of Registry');
        $explanation = __('The use of the Registry is deprecated. Use dependency injection or data persistors instead.');
        return [
            'title' => $title,
            'explanation' => $explanation,
            'files' => []
        ];
    }

    public function getProcessorName(): string
    {
        return __('Use of Registry');
    }

    public function getAuditSection(): string
    {
        return __('PHP');
    }

    public function run(): void
    {
        try {
            $className = $this->classNameGetter->getClassFullNameFromFile($this->getFile());
        } catch (NotAClassException|FileSystemException $e) {
            return;
        }

        try {
            if (!$this->constructorService->isConstructorOverridden($className)) {
                return;
            }
        } catch (\ReflectionException $e) {
            return;
        }

        // We get the constructor arguments
        $arguments = $this->definitions->getParameters($className);
        if (empty($arguments)) {
            return;
        }
        foreach ($arguments as $argument) {
            if ($argument === null) {
                continue;
            }
            if ($argument[1] === 'Magento\Framework\Registry') {
                $this->results['hasErrors'] = true;
                $this->results['errors']['useOfRegistry']['files'][] = $className;
            }
        }

    }
}