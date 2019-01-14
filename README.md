XWork
===
## 介绍
**xwork** 是一款轻量级的面向对象php开发框架，它基于`ORM、UnitOfWork(工作单元)、MVC`等。没有复杂的设计模式，没有复杂的外部接口，一切只为了简单，代码简单、逻辑清晰。尽可能的使用约定，消除不必要的配置文件。  
  这里没有中间件，没有控制反转，没有`Facade`，超轻量级路由，为了就是学习成本低、上手快，让你能够更快的投入到项目开发中去。  
  数据操作完全面向对象，自研的ORM模块配合工作单元，让您飞一般的玩转数据。当然这里牺牲了一点性能，但是对于web项目来说，那点性能可以忽略不计（个人认为，不喜勿喷）

## 架构
![xwork](http://st.daxiangbanka.com/photo/1/b6/1b6683dfe7127ba1ead18b34f12b9a66.png)  
架构还是标准的mvc+dao框架架构，特殊的是xwork深度结合ORM，这里没有选择，必须使用ORM。。。  
orm和工作单元的深度结合，使得关于表设计和逻辑组织都非常的简单、迅速和高效。
## 项目结构
```
├── audit
│   ├── ActionMap.properties.php 路由配置
│   ├── AuditBaseAction.php 子系统基类
│   ├── action 其实就是controller
│   │   └── DemoAction.php 里面的doMethod其实就是action
│   └── tpl 模板，直接php渲染吧，不想再学一门模板语法了
├── domain 重要！！ 领域逻辑，关于这点请阅读领域驱动设计
│   ├── BaseAction.php 所有子系统的基类action
│   ├── UrlFor.php url生成器
│   ├── dao 数据访问对象Dao，它返回的是实体
│   │   ├── HotelDao.php 
│   │   ├── RoomDao.php
│   │   └── UserDao.php
│   ├── entity 实体，表的映射
│   │   ├── Hotel.php
│   │   ├── Room.php
│   │   └── User.php
│   ├── lib 一些公共库
│   │   └── Util.php
│   └── service 服务层，封装一些复杂业务逻辑
│       ├── HotelService.php
│       ├── RoomService.php
│       └── UserService.php
├── script 脚本，包括但不限于定时脚本、消息队列消费者等等
├── sys
│   ├── PathDefine.php app路径的定义
│   └── config 配置文件，需要用symlink ln -svf config.prod.php config.php
│       ├── config.php
│       ├── config.sample.php
│       └── config.unit.php
├── www www子系统，一般用于官网主站
│   ├── ActionMap.properties.php
│   ├── WwwBaseAction.php
│   ├── action
│   │   ├── DemoAction.php
│   │   ├── HotelAction.php
│   │   ├── IndexAction.php
│   │   ├── RoomAction.php
│   │   └── UserAction.php
│   └── tpl
└── wwwroot
    ├── audit 子系统入口
    │   └── index.php
    └── www 子系统入口
        └── index.php
```
 你可以通过`xworker`脚手架来新建项目
 

## 安装
1. 给你的项目起个名字，比如：`mkdir pinduoduo`
2. `cd pinduoduo`
3. `composer require "nigestream/xwork"`
4. 接下来设置个环境变量 `export PATH=$PATH:vendor/bin`
5. 现在你可以执行`xworker`看一下效果了![xworker](http://st.daxiangbanka.com/photo/f/d7/fd728fbaec38ee9da81dc691ddd075a1.png)
6. 我们来初始化一个项目，执行命令`xworker make:init`， 会在当前目录下新建一个app目录，即上述**项目结构**所示
7. 以后你就可以用项目目录下的`xworker`来执行了, 比如:`php xworker make:new`（忘记第4步的环境变量吧）

**注意**：项目的entity目录使用classmap映射加载的，非PSR-4自动加载方式。  
所有新增的实体类必须放在app/domain/entity目录下，且需要执行`composer dumpautoload`来生成classmap；但如果使用xworker make:entity 则会自动执行` composer dumpautoload`。


## 模块设计
### Entity
`todo` @淘小金
### Dao
`todo` @叶孤城
### UnitOfWork
`todo` @老史
### MVC
`todo` @大哥大
## todos
- XController 需要重构
- 是否考虑引入一个高大上的路由？
- 尽可能的暴露一些用户可自定义的接口
- 完善文档
- 清理代码，删除一些没必要的文件

## Contributors
- 老史
- 淘小金
- 叶孤城
- 大哥大
