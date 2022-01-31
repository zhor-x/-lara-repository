<?php

namespace ZhorX\Laravel\Repo\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class RepositoryServiceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Interface and Repository files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * @var string
     */
    protected $defaultRepositoryNamespace = 'Repositories';
    /**
     * @var string
     */
    protected $defaultInterfaceNamespace = 'Interfaces';
    /**
     * @var string
     */
    protected $defaultModel = 'Models';
    /**
     * @var string
     */
    protected $baseRepositoryInterface = 'RepositoryInterface';
    /**
     * @var
     */
    /**
     * @var
     */
    protected $interfaceClass, $repositoryClass;
    /**
     * @var
     */
    protected $modelClass;
    /**
     * @var
     */
    protected $input;
    /**
     * @var
     */
    protected $name;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->getNameInput();
            $this->makeDirectories();
            $this->allocateOrCreateModel();
            $this->createInterface();
            $this->createRepository();
            $this->mergeRepositoryConfig();
            $this->info($this->type.' created successfully.');
        } catch (\Exception $e) {
            report($e);
            $this->error($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function getNameInput()
    {
        $this->input = trim($this->argument('file'));
    }

    /**
     * @return void
     */
    public function makeDirectories()
    {
        $this->makeRepositoryDirectory();
        $this->makeInterfaceDirectory();
    }

    /**
     * @return string
     */
    public function getDefaultRepositoryNamespace()
    {
        return $this->rootNamespace() . '' . $this->defaultRepositoryNamespace;
    }

    /**
     * @return string
     */
    public function getDefaultInterfaceNamespace()
    {
        return $this->rootNamespace() . '' . $this->defaultInterfaceNamespace;
    }

    /**
     * @return string
     */
    public function getDefaultModelsNamespace()
    {
        return $this->rootNamespace() . '' . $this->defaultModel;
    }

    /**
     * @param $class
     * @param $isInterface
     * @return string
     */
    public function qualifyFile($class, $isInterface = false)
    {
        $class = ltrim($class, '\\/');

        $rootNamespace = $isInterface ? $this->getDefaultInterfaceNamespace() : $this->getDefaultRepositoryNamespace();
        $this->modelClass = $this->getDefaultModelsNamespace();
        if (Str::startsWith($class, $rootNamespace)) {
            return $class;
        }

        $class = str_replace('/', '\\', $class);

        return $this->qualifyFile(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $class, $isInterface
        );
    }

    /**
     * @param $interface
     * @return void
     */
    public function createInterface($interface = null)
    {
        $interface = $interface ?? $this->input;
        $interfaceName = $this->qualifyFile($interface, true) . 'Interface';
        $path = $this->getPath($interfaceName);
        $this->info($path);

        if ($this->files->missing($path)) {
            $this->makeDirectory($path);

            $this->files->put($path, $this->sortImports($this->buildInterface($interfaceName)));


        }
        $this->interfaceClass = $interfaceName;
    }

    /**
     * @param $interfaceName
     * @param $baseStub
     * @return array|string|string[]
     */
    protected function buildInterface($interfaceName, $baseStub = null)
    {

        $stub = $this->files->get($baseStub ?? $this->getInterfaceStub());

        return $this->replaceNamespaceInterface($stub, $interfaceName);
    }

    /**
     * @return string
     */
    protected function getInterfaceStub()
    {
        return __DIR__ . '/../stubs/item-repository-interface.stub';
    }

    /**
     * @param $stub
     * @param $interfaceName
     * @return array|string|string[]
     */
    public function replaceNamespaceInterface(&$stub, $interfaceName)
    {
        return str_replace(
            ['DummyNamespace', 'DummyItemRepositoryInterface'],
            [$this->getNamespace($interfaceName), $this->replaceClassName($interfaceName)],
            $stub
        );
    }

    /**
     * @param $name
     * @return array|string|string[]
     */
    protected function replaceClassName($name)
    {
        return str_replace($this->getNamespace($name) . '\\', '', $name);
    }

    /**
     * @return void
     */
    public function makeRepositoryDirectory()
    {
        $repositoryPath = app_path() . '/' . $this->defaultRepositoryNamespace;
        if ($this->files->exists($repositoryPath)) {
            return;
        }
        $this->files->makeDirectory($repositoryPath, 0777, true, true);
    }

    /**
     * @return void
     */
    public function makeInterfaceDirectory()
    {
        $interfacePath = app_path() . '/' . $this->defaultInterfaceNamespace;
        if ($this->files->exists($interfacePath)) {
            return;
        }
        $this->files->makeDirectory($interfacePath, 0777, true, true);
    }

    /**
     * Create repository file
     *
     * @param mixed $repo
     *
     * @return void
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function createRepository($repo = null)
    {
        $repository = $repo ?? $this->input;
        $this->name = $this->qualifyFile($repository);
        $name = $this->name . 'Repository';
        $path = $this->getPath($name);
        if ($this->files->missing($path)) {
            $this->makeDirectory($path);
            $this->files->put($path, $this->sortImports($this->buildRepository($name, $this->interfaceClass)));

            $this->repositoryClass = $name;
        }
    }

    /**
     * Build repository
     *
     * @param string $name
     * @param string $interface
     * @param string $baseStub
     *
     * @return void
     */
    protected function buildRepository($name, $interface, $baseStub = null)
    {
        $stub = $this->files->get($baseStub ?? $this->getRepositoryStub());

        return $this->replaceRepository($stub, $name, $interface);
    }

    /**
     * Replace repository content
     *
     * @param string $stub
     * @param string $name
     * @param string $interface
     *
     * @return void
     */
    public function replaceRepository(&$stub, $name, $interface)
    {
        $tmp = explode('\\', $interface);
        $interfaceNamespace = $interface;
        $interface = trim(array_pop($tmp));
        $modelClass = str_replace('/', '\\', $this->modelClass . '/' . $this->input);

        return str_replace(
            ['DummyNamespace', 'DummyRepositoryClass', 'DummyRepositoryInterfaceNamespace', 'DummyRepositoryInterface', 'DummyModelClass', 'DummyModel', 'DummyVariable'],
            [$this->getNamespace($name), $this->replaceClassName($name), $interfaceNamespace, $interface, $modelClass, $this->replaceClassName($this->name), Str::lower($this->replaceClassName($this->name))],
            $stub
        );
    }

    /**
     * @return string
     */
    public function getRepositoryStub()
    {
        return __DIR__ . '/..//stubs/item-repository.stub';
    }

    /**
     * Get repository stub file
     *
     * @return string
     */
    protected function getStub()
    {
        return;
    }

    /**
     * @param $model
     * @return void
     */
    public function allocateOrCreateModel($model = null)
    {
        $model = $model ?? $this->input;
        $name = $this->qualifyClass($model);
        $path = $this->getPath($name);

        if ($this->files->missing($path) && $this->confirm('This model is not existed. Do you wish to create?')) {
            Artisan::call('make:model', ['name' => $this->input]);
        }

        $this->modelClass = '\\' . $name;
    }

    /**
     * @return void
     */
    public function mergeRepositoryConfig()
    {
        $filePath = config_path('repositories.php');
        $content = config('repositories');

        if ($this->files->missing($filePath)) {
            $content = [];
        }

        if ($this->interfaceClass && $this->repositoryClass) {
            $content = array_merge($content, [
                $this->interfaceClass => $this->repositoryClass
            ]);
        }

        $stub = $this->files->get(__DIR__ . '/../stubs/repositories.stub');
        $repo = '';
        foreach ($content as $key => $value) {
            $repo .= "\t\\" . $key . "::class => \\" . $value . "::class,\n";
        }
        $stub = str_replace('__RepositoryArray__', $repo, $stub);

        $this->files->put($filePath, $stub);
    }
}
