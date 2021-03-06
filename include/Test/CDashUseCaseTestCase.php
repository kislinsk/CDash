<?php
namespace CDash\Test;

use CDash\ServiceContainer;
use CDash\Test\UseCase\UseCase;

class CDashUseCaseTestCase extends CDashTestCase
{
    /** @var  ServiceContainer $originalServiceContainer */
    private $originalServiceContainer;
    private $model_id_cache;

    public function setUp()
    {
        parent::setUp();
        $this->model_id_cache = [];
    }

    public function tearDown()
    {
        if ($this->originalServiceContainer) {
            ServiceContainer::setInstance(
                ServiceContainer::class,
                $this->originalServiceContainer
            );
        }
        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    /**
     * @param object $model
     * @return void
     */
    private function setCachedModelId($model)
    {
        if (isset($model->SubProjectName)) {
            $this->model_id_cache[$model->SubProjectName] = $model->Id;
        } elseif (isset($model->Name)) {
            $this->model_id_cache[$model->Name] = (int) $model->Id;
        }
    }

    /**
     * @param $name
     * @return int
     */
    protected function getCachedModelId($name)
    {
        return $this->model_id_cache[$name];
    }

    public function setUseCaseModelFactory(UseCase $useCase)
    {
        $this->setDatabaseMocked();
        $this->originalServiceContainer = ServiceContainer::getInstance();

        $mockServiceContainer = $this->getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $mockServiceContainer
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($class_name) use ($useCase) {
                $model = $this->getMockBuilder($class_name)
                    ->setMethods(['Insert', 'Update', 'Save', 'GetCommitAuthors', 'GetMissingTests'])
                    ->getMock();

                $model->expects($this->any())
                    ->method('Save')
                    ->willReturnCallback(function () use ($class_name, $model, $useCase) {
                        $model->Id = $useCase->getIdForClass($class_name);
                        if (isset($model->Errors)) {
                            foreach ($model->Errors as $error) {
                                $error->BuildId = $model->Id;
                            }
                        }
                        $this->setCachedModelId($model);
                        return $model->Id;
                    });

                $model->expects($this->any())
                    ->method('Insert')
                    ->willReturnCallback(function () use ($class_name, $model, $useCase) {
                        // TODO: discuss
                        if (!property_exists($model, 'Id')) {
                            $model->Id = null;
                        }

                        if (!$model->Id) {
                            $model->Id = $useCase->getIdForClass($class_name);
                            $this->setCachedModelId($model);
                        }
                        return $model->Id;
                    });

                $model->expects($this->any())
                    ->method('GetCommitAuthors')
                    ->willReturnCallback(function () use ($useCase, $model) {
                        /** @var \Build $model */
                        return $useCase->getAuthors($model->SubProjectName);
                    });

                $model->expects($this->any())
                    ->method('GetMissingTests')
                    ->willReturnCallback(function () use ($useCase) {
                        $missing = [];
                        if (isset($useCase->missingTests)) {
                            $missing = $useCase->missingTests;
                        }
                        return $missing;
                    });

                return $model;
            });

        ServiceContainer::setInstance(ServiceContainer::class, $mockServiceContainer);
    }
}
