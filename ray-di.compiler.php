<?php
require __DIR__ . '/psr-11-v2/vendor/autoload.php';
require __DIR__ . '/setup.php';

use Ray\Compiler\DiCompiler;
use Ray\Compiler\ScriptInjector as ScriptInjector;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\ProviderInterface;

class DsnProvider implements ProviderInterface{
    public function get(): string
    {
        return getenv('DB_DSN');
    }
}

class UserProvider implements ProviderInterface
{
    public function get(): string
    {
        return getenv('DB_USERNAME');
    }
}

class PasswordProvider implements ProviderInterface
{
    public function get(): string
    {
        return getenv('DB_PASSWORD');
    }
}

class Module extends AbstractModule
{
    protected function configure()
    {
        // Gives a dependency name to the constructor argument of PDO.
        $this->bind(PDO::class)->toConstructor(PDO::class, ['dsn' => 'db_dsn', 'username' => 'db_username', 'password' => 'db_password']);
        // Bind a Provider to each dependency name with toProvider() method
        $this->bind()->annotatedWith('db_dsn')->toProvider(DsnProvider::class);
        $this->bind()->annotatedWith('db_user')->toProvider(UserProvider::class);
        $this->bind()->annotatedWith('db_password')->toProvider(PasswordProvider::class);
        // Gives a dependency name to the constructor argument of Foo.
        $this->bind(Foo::class)->toConstructor(Foo::CLASS, ['bar' => 'bar', 'baz' => 'baz']);
        // Bind an instanse to each dependency name with toInstance() method.
        $this->bind()->annotatedWith('bar')->toInstance('bar-wrong');
        $this->bind()->annotatedWith('baz')->toInstance('baz-right');
    }
};
$module = new Module;
// Redefine Foo $bar
$module->override(
    new class extends AbstractModule {
        protected function configure()
        {
            $this->bind()->annotatedWith('bar')->toInstance('bar-right');
        }
    }
);

$tmpDir = __DIR__ . '/tmp';
// compile
if ((count(glob($tmpDir))) !== 0 ) {
    $compiler = new DiCompiler($module, $tmpDir);
    $compiler->compile();
}
$injector = new ScriptInjector($tmpDir);

echo "Ray.Di" . PHP_EOL;
output($injector, 'getInstance');
