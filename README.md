# Comparing Capsule To Other Containers

Japanese | [English](https://github.com/ray-di/comparison/blob/ray.di/README.md)

AWDI（Autowiring Dependency Injection）コンテナの比較を始めたとき、補助的な追加機能は別として、システム間の違いはその中核部分にはないだろうと推測していました。その違いは、それをどのように行うかにあるのだろうと予想していました。

結果的に、私は間違っていたのです。私が基本的な機能だと考えていたものは、AWDIのシステムによっては、少なくとも、何らかの指示や余分な努力、回避策がなければ使えないものでした。

## シナリオ

私が想像していた基本機能のシナリオは、簡単にできるものだと思っていた。
すべてのAWDIシステムで、このような作業が行われました。

- [_Foo_](./setup.php) クラスのインスタンスを定義し、後からコンテナから取得します。
  
- コンストラクタの引数をひとつだけオーバーライドして、Foo にもともと設定されている値のひとつを別の値に置き換えます。(これは複数の設定ファイルの読み込みをシミュレートしています)。
  
- *Foo*の従属オブジェクトにある1つ以上の値を、環境などから遅延解決します。(これは遅延解決の機能を示しています)。

しかし、簡単だと思ったことが、いくつかのAWDIコンテナでは難しかったり、不可能だったりすることが判明しました。

以下では、Auryn, League Container, Illuminate Container, PHP-DI, Symfony Dependency Injection, Ray.Diを使ったシナリオのコードをご覧いただけます。各コンテナの例では、同じ[setup](./setup.php)と結果を確認するための `output()` 関数を使用しています。

### Capsule

カプセルはこの比較の中心的存在であるため、ここから始めるのが理にかなっています。
を使用します。Capsuleのコードは<https://github.com/capsulephp/di>にあります。
シナリオのサンプルコードは[こちら](./capsule.php)です。

Capsuleは、シナリオを完成させるためのものです。

1. 各定義はコンテナの動的プロパティで、`{}`記法を用いて記述されます。

2. PDOの引数は、クラス定義の `env()` メソッドを使って環境から遅延して解決されます。

3. 明示的な _Foo_ 引数はリテラルです。$bar 引数は、正しくオーバーライドされているかどうかを後で確認するために、わざと「間違った」値に設定されています。

4. 引数 _Foo_ $bar は、オーバーライド値でロードされる新しい構成をシミュレートするために再定義されています。

`output()`は正しいです。

```
PDO
bar-right
baz-right
```

### Auryn

Aurynのコードは<https://github.com/rdlowrey/auryn>に掲載されています。サンプル
シナリオのコードは[こちら](./auryn.php)です。

Aurynは**シナリオを完成させません**。

1. 環境変数を読み込むためのレイジーローディング機能がないようです。 従って、_PDO_の引数を直接指定することはできません。 コンテナ 代わりに、インジェクタはデリゲートファクトリクロージャを必要とします。 これは、_PDO_の引数が二次的にレイジーローディングされることを意味します。

2. 明示的な _Foo_ の引数名には、それを示す `:` を前置する必要があります。引数 $bar はわざと "wrong" に設定されています。正しくオーバーライドされているかどうか、後で確認することができます。

3.個々の引数に対応する方法はないようなので、`define()`は が再び呼び出され、引数 $bar が再定義されます。不幸なことに、`define()` は引数のひとつだけでなく、_Foo_ の定義全体を上書きします。

その結果、`output()` は失敗し、$baz のデフォルト値が表示されます。

```
PDO
bar-right
baz-wrong
```

### Illuminate Container

Illuminate Containerのコード（Laravelコンポーネント）は、<https://github.com/illuminate/container>に掲載されています。シナリオのサンプルコードは[こちら](./illuminate.php)です。

Illuminate Containerは、シナリオを完成させますが、余分な労力を必要とします。

1. _PDO_の引数は、`when()->needs()`イディオムを介して、パラメータ名のプレフィックスに `$` を付けて個別に指定する必要があります。

2. _PDO_ の引数は、環境そのものからではなく、 `giveConfig()` を介して設定ソースから取得しなければなりません。(以下のポイント5を参照)

3. 明示的な _Foo_ 引数も同様に、個別に指定する必要があります。$bar 引数は、後で正しくオーバーライドされるかどうかを確認するために、あえて "wrong" に設定されています。

4. _Foo_ $bar 引数は再定義され、オーバーライド値でロードされる新しい構成をシミュレートします。

5. `giveConfig()` メソッドは、 'config'` というコンテナエントリオブジェクトと `get()` メソッドが存在することを想定しています。これを実現するために、config コンテナファクトリークロージャーが作成され、メインコンテナにバインドされます；必要な config 値は環境から取得されます。このように、環境の値はレイジーローディングされますが、間接的に、そしてセカンドハンドのような形でロードされます。

`output()`は正しいです。

```
PDO
bar-right
baz-right
```

### League Container

リーグコンテナのコードは https://github.com/thephpleague/container にあります。シナリオのサンプルコードはこちらです。

League Container は、余分な努力をしても、シナリオを完成させることはできません。

ReflectionContainerをフォールバックのデリゲートとして設定しない限り、コンテナ自体が自動配線されることはありません。

PDO の引数は遅延解決可能として指定されていますが、その解決は環境から直接ではなくコンテナを経由して行わなければなりません。(以下のポイント 6 を参照ください)。

Foo $pdo 引数は遅延解決可能 (lazy-resolvable) として指定されていますが、 ここで紹介する他のどのコンテナでもこの指定は必要ありません。

Foo $bar 引数は遅延解決可能として指定されています。これは、個々のコンストラクタ引数をオーバーライドする方法がないためです。回避策として、値を定義して後で再定義できるように、コンテナの外側で遅延解決されます。

Foo $baz 引数をリテラル文字列オブジェクトとして指定し、コンテナに対してそれ以上解決しようとしないように指示します。

Foo $bar の初期値、そして getenv() でクロージャとして遅延ロードされた PDO 引数です。

コンテナには Foo $bar の新しい値が設定され、 オーバーライドされた複数の設定が読み込まれることをシミュレートします。

残念ながら、リーグコンテナの内部動作の関係で、 ポイント 7 で設定した内容はポイント 4 で設定した内容をオーバーライドできないため、 output() は失敗します。
```
PDO
bar-wrong
baz-right
```

具体的には、DefinitionAggregate::getDefinition()ループは、最初にマッチするキーを見つけた後に停止するからです。

```php
public function getDefinition(string $id): DefinitionInterface
{
    foreach ($this->getIterator() as $definition) {
        if ($id === $definition->getAlias()) {
            return $definition->setContainer($this->getContainer());
        }
    }

    // ...
}
```

### PHP-DI

PHP-DIのコードは https://github.com/PHP-DI/PHP-DI で見ることができます。このシナリオのサンプルコードはこちらです。

PHP-DI はシナリオを完成させますが、少し手間がかかります。

コンテナ自体に、自動配線とアノテーションを使用しないように指示する必要があります。

PDO の引数は、環境から直接遅延ロードされます。

Foo $bar 引数は遅延解決可能 (lazy-resolvable) として指定されています。これは、コンストラクタの個々の引数をオーバーライドする方法がないためです。回避策として、この値はコンテナの外で遅延解決され、後で再定義できるようになります。

Foo $baz 引数はリテラル文字列として指定されます。

Foo:barエントリが、Foo $barの初期値とともに追加されます。

コンテナは、Foo $bar の新しい値で再セットされます。これは、オーバーライドを含む複数の設定のロードをシミュレートするためです。

`output()`は正しいです。

```
PDO
bar-right
baz-right
```

### Symfony Dependency Injection

symfony の Dependency Injection のコードは https://github.com/symfony/dependency-injection で見ることができます。シナリオのサンプルコードはこちらです。

symfony はシナリオを完成させません。symfony のコンテナを使う前にコンパイルする必要があります; 順番に、コンテナがコンパイルされる前に環境変数が配置されている必要があります。環境変数が利用可能になる前にコンテナをコンパイルすると、EnvNotFoundExceptions が発生します。

symfony のコードを実行する唯一の方法は、コンテナをコンパイルして使用する前に環境変数を定義することですが、これは検討中の他のどのシステムでも要求されていないことです。

PDO の引数は '%env(DB_DSN)%' のような特別な文字列表記で環境変数として指定されます。

Foo クラスは autowired とマークされ、さらにどこからでも取得できるパブリックサービスとしてマークされます。

Foo の明示的な引数も同様に個別に指定する必要があります。$bar 引数は、正しくオーバーライドされているかどうかを後で確認するために、わざと「誤り」に設定されています。

Foo $bar 引数は再定義され、オーバーライド値でロードされる新しい構成をシミュレートします。

EnvNotFoundExceptions を回避するため、コンテナをコンパイルする前に環境変数を定義しています。

コンテナは、使用前にコンパイルされます。

変更されたシナリオの出力は正しいです。

...
```
PDO
bar-right
baz-right
```

...コンテナをコンパイルして使用する前に環境をロードする必要があるため、これは環境変数の「遅延ロード」にカウントされないと思うのですが。結果的に、これは「シナリオを完成させていない」と判断せざるを得ません。

### Ray.Di

Ray.Diのコードは<https://github.com/ray-di/Ray.Di>に掲載されています。サンプル
シナリオのコードは[こちら](./ray-di.php)です。

Ray.Diはシナリオを完成させるためのものです。

1. PDO の引数は、実行時にその値を提供する [Provider](https://github.com/ray-di/Ray.Di#provider-bindings) クラスに束縛されています。
2. $bar 引数は、正しくオーバーライドされているかどうかを後で確認するために、 [インスタンスバインディング](https://github.com/ray-di/Ray.Di#instance-bindings) でわざと「間違った」値に設定されています。
3. _Foo_ $bar 引数は、オーバーライド値でロードされる新しい設定をシミュレートするために、再定義されます。

`output()`は正しいです。

```
PDO
bar-right
baz-right
```

## Summary

Thus, the final tally of which container systems completed the scenario:

- Capsule: yes
- Auryn: no
- League: no
- Illuminate: yes, with workarounds
- PHP-DI: yes, with workarounds
- Symfony: no
- Ray.Di: yes

これは、シナリオを完成させられなかったコンテナが「悪い」「間違っている」ということでしょうか？いいえ。しかし、このシナリオで強調された機能が当たり前のものだと考えたのは間違いだったということです。別のシナリオでは、これらのコンテナをより良く、あるいはより悪く見せることができるかもしれません。

いずれにせよ、この演習では、実際の例を使って、例のコンテナシステムの違いをいくつか示しました。

## Appendix: Running The Scenario

You can run the comparison code for yourself.

First, install the packages being compared ...

```sh
cd psr-11-v1; composer install
cd psr-11-v2; composer install
```

... then run the example code of your choice:

```sh
php capsule.php
php php-di.php
# etc
```
