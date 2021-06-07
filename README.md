study-api
----------------------------------------------------

### 环境要求

* PHP >= 7.2.5
* BCMath PHP 拓展
* Ctype PHP 拓展
* Fileinfo PHP 拓展
* JSON PHP 拓展
* Mbstring PHP 拓展
* OpenSSL PHP 拓展
* PDO PHP 拓展
* Tokenizer PHP 拓展
* XML PHP 拓展

> Ps:缺拓展或版本不够会报错，类似`Your requirements could not be resolved to an installable set of packages`

### 程序安装
* 下载工程

```
git clone git@github.com:jayding8/study-api.git -b develop
```

* 安装composer依赖

```
composer install --no-scripts
```
* 复制配置文件，并按需配置好本地环境   

```
cp .env.example .env
```
* 生成随机串

```
php artisan key:generate
```


### Nginx参考配置 

```
server {
        listen       80;
        server_name  study-api.jayding.top;
        set $htdocs /data/www/study-api/public;


        location / {
            root   $htdocs;
            index  index.php index.html index.htm;
            try_files $uri $uri/ /index.php?$query_string;
        }

        error_page   500 502 503 504  /50x.html;
        location = /50x.html {
            root   html;
        }

#       include /etc/nginx/default.d/*.conf;
        location ~ \.php(.*)$ {
            root   $htdocs;
            #fastcgi_pass   127.0.0.1:9000;
            fastcgi_pass   unix:/dev/shm/php-cgi72.sock;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO $1;
            include        fastcgi_params;
        }
}
```
