laravel4-sae
============
只需手动增加一行代码即可让Laravel4（~4.2）运行在SAE，而且在本地和在SAE开发无需命令切换，自动判断环境并切换配置。

## 安装

####在SAE安装Laravel
在SAE安装Laravel与本地环境安装稍有区别：

1. 在SAE的“应用管理”中新建一个没有代码的应用，比如叫laravel；

2. 在“laravel”的“代码管理”中新建一个版本，比方为1；

3. 用svn将其同步到本地，你会看到目录结构为laravel/1/，注意**这个1才是你的网站根目录**。以下不再说明

在命令行窗口中定位到网站根目录，输入：

```
composer create-project laravel/laravel=4.1.* your-project-name --prefer-dist
```
**SAE的php版本为5.3，因此最高只能支持到Laravel4.1。（Laravel4.2用到了php5.4的trait特性）**

然后用composer加入laravel4-sae。

```
composer require chariothy/laravel4-sae dev-master
```

它会更新网站根项目下的composer.json，并将laravel4-sae安装到vendor目录下。

## 如何使用

好了，要增加的**唯一**一行代码来了：
打开网站项目根目录下app/start/artisan.php，在结尾处添加
```
Artisan::add(new Chariothy\SaePatch);
```
保存之后，打开cmd窗口，定位到你的网站项目根目录下，输入
```
php artisan sae
```
好了，正常情况下会输出一堆Successfully：
```
- [config]     Successfully created folder 'app/config/sae'
-              Successfully backed up 'app/config/sae/database.php.'
  [db]         Successfully added file 'app/config/sae/database.php.'.
-              Successfully backed up 'app/config/sae/app.php'
  [app]        Successfully added file 'app/config/sae/app.php'.
-              Successfully backed up 'index.sae.php'
  [index]      Successfully added file 'index.sae.php'.
-              Successfully backed up 'config.yaml'
  [yaml]       Successfully added file 'config.yaml'.
-              Successfully backed up 'favicon.ico'
  [favicon]    Successfully added file 'favicon.ico'.
-              Successfully backed up 'bootstrap/start.php'
  [env]        Successfully patched 'detectEnvironment' for sae.
-              Successfully backed up 'bootstrap/start.php'
  [wrap]       Successfully patched 'wrap storage' for sae.
-              Successfully backed up 'app/start/global.php'
  [log]        Successfully patched 'SaeDebugHandler' for sae.
- THE END.
```
这就是全部。现在你可以用svn上传到SAE，打开首页将看到熟悉的“You have arrived.”

## SaePatch都做了啥？
以下对输出的结果做解释：
```
- [config]     创建了一个目录 'app/config/sae'，其中是在SAE环境下的设置。
- [db]         SAE环境下的database设置。
- [app]        SAE环境下的app设置。
- [index]      在根目录下创建'index.sae.php'，这是为了满足SAE的目录结构不像在本地时以public为根目录。
- [yaml]       在根目录下创建'config.yaml'，这是SAE的rewrite规则.
- [favicon]    在根目录下创建空的'favicon.ico'，因为SAE的目录结构不像在本地时以public为根目录。
- [env]        在'bootstrap/start.php'增加一个'detectEnvironment'来检测SAE环境。
- [wrap]       在'bootstrap/start.php'中为'storage'目录添加[SAE wrappers](http://sae.sina.com.cn/doc/php/runtime.html#wrappers "")。
- [log]        在'app/start/global.php'中增加'SaeDebugHandler'来调用SAE的sae_debug()。
```

## --overwrite选项
默认情况下SaePatch会忽略掉已经打过的补丁，不过你可以用--overwrite来覆盖它，可以全部覆盖，也可以选择覆盖。不用担心，都会先备份的。具体参数：
```
Options:
 --overwrite (-o)      Patch laravel4 even it has been patched before.

                       Option value:
                       config   Add folder app/config/sae.
                       db       Add file app/config/sae/database.php..
                       app      Add file app/config/sae/app.php.
                       index    Add file index.sae.php.
                       yaml     Add file config.yaml.
                       favicon  Add file favicon.ico.
                       env      Add closure for $app->detectEnvironment().
                       wrap     Wrap storage path with SAE wrapper prefix.
                       log      Add SaeDebugHandler for MonoLog.
                       all      overwrite all above.

                       Example1: php artisan sae -o db
                       Example2: php artisan sae -o all
```

## 所有选项
```
php artisan sae -h
```
可以看到所有选项

Have fun!