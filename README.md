laravel4-sae v1.1.0
============
只需手动增加两行代码即可让Laravel4（ < 4.2）运行在SAE，而且在本地和在SAE开发无需命令切换，自动判断环境并切换配置。

## 安装

####在SAE安装Laravel
在SAE安装Laravel与本地环境安装稍有区别：

1. 在SAE的“应用管理”中新建一个没有代码的应用，比如叫projectname；
2. 用svn将其同步到本地，你会看到本地多出个目录projectname；
3. 打开在命令行窗口，定位到projectname，创建一个laravel应用，输入

```
composer create-project laravel/laravel=4.1.* project-version --prefer-dist
```
**！注意**上面命令中的project-version，这应该是个数字，是你还没用过的SAE应用的版本号，对新应用来说从1开始。下文中指的网站根目录是指projectname/project-version，切记。

**SAE的php版本为5.3，因此最高只能支持到Laravel4.1.x。（Laravel4.2用到了php5.4的trait特性）**

漫长的等待后安装成功，然后cmd窗口中定位到projectname/project-version，用composer加入laravel4-sae，输入：

```
composer require chariothy/laravel4-sae dev-master
```

它会更新网站根项目下的composer.json，并将laravel4-sae安装到vendor目录下。
最后用svn将整个应用上传到SAE，“应用管理”的“代码管理”下就会多出一个版本号，**在“服务管理”的“KVDB”中开启KVDB服务**。

## 如何使用

好了，要增加的两行代码来了：
打开网站项目根目录下app/config/app.php，找到'providers'设置，在结尾处添加'Chariothy\SaeServiceProvider'：
```
'providers' => array(

        ......
        'Chariothy\SaeServiceProvider',
	),
```
找到'aliases'设置，在结尾处添加'SAE' => 'Chariothy\SaeFacade'：
```
'aliases' => array(

		......

        'SAE' => 'Chariothy\SaeFacade',
	),
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
这就是全部。现在你可以用svn上传到SAE（**不要忘记先在SAE中开启KVDB服务！**），
打开首页将看到熟悉的“You have arrived.”

## 如何添加静态链接
在laravel中是用{{HTML::stript('js/code.js')}}、{{HTML::style('css/style.js')}}、{{HTML::image('img/image.png')}}这三句来添加。
但前提是在本地部署，同时将root/public设置为根目录。而在SAE上，一般会将图片等资源放在storage中。
这里laravel4-sae在app/config/sae/app.php下添加了几个设置项：
```
'sae' => array(
        //这是用来设置你的缓存等存放在SAE的KVDB中
        'wrapper' => 'saekv://',


        //这里是开启storage时设置的domain，具体值自己设置
        'domain' => 'example',
        
        //这里指定了三种资源的存放位置，值有'code'和'storage'
        //'code'则放在root/public相应的目录下
        //'storage'会放在SAE的storage相应的目录下
        'style'     => 'code',
        'script'    => 'code',
        'image'     => 'storage',
    ),
```
这样你在代码中只要用{{SAE::stript('js/code.js')}}、{{SAE::style('css/style.js')}}、{{SAE::image('img/image.png')}}这三句即可。
同时这三句对本地部署同样适用，无需切换。

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

## 使用示例
见HOW-TO.MD

## 特别注意
在SAE环境下，如需切换memcached、storage、kvdb，则config.cache.drive和config.session.drive均保持file不变，只需在config.sae.app（在config/sae/app.php中）中改变wrapper属性即可。

事实上，SAE的storage至少目前不支持文件append，而memcache又太贵，所以就用默认的kvdb来保存字符挺好的，storage还是适合放些静态图片等等，memcache等着访问量上去了再换也不迟。

另外，**可别忘了在SAE的控制面板中打开kvdb等相应的服务哦~**

Have fun!

**PS: 为了方便那些composer速度太慢的朋友，我用laravel4-sae打包了一个laravel 4.1.27，直接解压出来就可以上传到SAE运行，[这里下载](http://download.csdn.net/detail/thy38/8170417)。**

## 更新日志
v 1.1.0 
提供对静态资源在storage的支持，感谢flyboy

v 1.0.0 初始版本
通过sae命令可以对laravel打上sae补丁