<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\RedCapApiConnectionInterface;

/**
 * Factory class for creating a RedCapProject instance on demand.
 *
 * Holds off creating the RedCapProject until someone asks for it. Once the
 * project is created, the same instance will be used for subsequent
 * requests for the project from the same instance of this class.
 * <p>
 * It is also possible to create a singleton instance of this class by
 * calling instance(). Subsequently, RedCapProjectFactory::instance() can be
 * called to fetch the same instance of the factory. As with an instance of
 * the class, a project is not actually created until getProject() is called.
 */
class RedCapProjectFactory implements RedCapProjectFactoryIntf
{
    // Allow for null to support unit testing
    protected static ?RedCapProjectFactory $singletonFactory = null;

    /** @var list<RedCapProjectInfo> $projects */
    private array $projects = [];

    /**
     * Fetch a singleton instance of the factory.
     *
     * @return RedCapProjectFactoryIntf
     */
    public static function instance(): RedCapProjectFactoryIntf
    {
        if (!isset(self::$singletonFactory)) {
            self::$singletonFactory = new RedCapProjectFactory();
        }

        return self::$singletonFactory;
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\RedCapProjectFactoryIntf::initializeProject()
     */
    public function initializeProject(
        string $apiUrl,
        string $apiToken,
        bool $sslVerify = true,
        ?string $caCertificateFile = null,
        ?ErrorHandlerInterface $errorHandler = null,
        ?RedCapApiConnectionInterface $connection = null
        ): string
    {
        // Check if there is an existing entry for this url/token pair
        $info = $this->findByProjectInfoByUrlToken($apiUrl, $apiToken);

        if (!$info) {
            $info = new RedCapProjectInfo(
                $this->generateProjectId(),
                $apiUrl,
                $apiToken,
                $sslVerify,
                $caCertificateFile,
                $errorHandler,
                $connection
                );

            $this->projects[] = $info;
        }

        return $info->getId();
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\REDCap\RedCapProjectFactoryIntf::getProject()
     */
    public function getProject(string $id): ?RedCapProject
    {
        $projectInfo = $this->findByProjectInfoById($id);

        if ($projectInfo) {
            $project = $projectInfo->getProject();

            if (is_null($project)) {
                $project = $this->createProject($projectInfo);
                $projectInfo->setProject($project);
            }
        } else {
            $project = null;
        }

        return $project;
    }

    private function findByProjectInfoById(string $id): ?RedCapProjectInfo
    {
        $projects = array_filter(
            $this->projects,
            fn($project) => $project->getId() == $id
            );

        return empty($projects) ? null : array_shift($projects);
    }

    private function findByProjectInfoByUrlToken(string $url, string $token): ?RedCapProjectInfo
    {
        $projects = array_filter(
            $this->projects,
            fn($project) => $project->getApiUrl() == $url && $project->getApiToken() == $token
            );

        return empty($projects) ? null : array_shift($projects);
    }

    /**
     * Generates a unique string identifier for a project, ensuring it does
     * not already exist for a project.
     *
     * @return string
     */
    protected function generateProjectId(): string
    {
        $id = uniqid('rpf');

        while (array_key_exists($id, $this->projects)) {
            $id = uniqid('rpf');
        }

        return $id;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function createProject(RedCapProjectInfo $info): RedCapProject
    {
        return new RedCapProject(
            $info->getApiUrl(),
            $info->getApiToken(),
            $info->getSslVerify(),
            $info->getCaCertificateFile(),
            $info->getErrorHandler(),
            $info->getConnection()
            );
    }
}
