# 让Laravel在SAE上跑起来

## 命令
```
帮助
php artisan sae -h

打全部补丁
php artisan sae -o all

打单个补丁
php artisan sae -o [component]
```

## 设置
所有设置均在app/config/sae下可以找到。
database.php为SAE上的数据库设置；
app.php为SAE上的相关前缀设置。

## 使用
对于缓存等，使用是透明的，跟原来一样。
如果在本地测试之后SVN提交到SAE上，刷新页面，laravel可能会说找不到app/storage下的某个文件，这很正常。
因为你在本地生成的这些缓存到了SAE上就没有用了，路径被指向了KVDB等地方。你只要再刷新一次页面就OK了，laravel会重新将缓存写入SAE上的新地址。

对于静态资源，只要将laravel的语法
{{HTML::stript('js/code.js')}}、{{HTML::style('css/style.js')}}、{{HTML::image('img/image.png')}}更换为
{{SAE::stript('js/code.js')}}、 {{SAE::style('css/style.js')}}、 {{SAE::image('img/image.png')}}即可。
同时适用于本地和SAE。