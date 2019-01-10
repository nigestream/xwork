nigestream/xwork
=====

## 快速开始
- 添加composer依赖

```json
"require": {
        "nigestream/xwork" : "~1.0"
    }
```
**注**: master是最新稳定版

- 使用php xwork 命令生成器构建项目

`php xwork init [appname]`

**注意**：项目的entity目录使用classmap映射加载的，非PSR-4自动加载方式。  
所有新增的实体类必须放在app/domain/entity目录下，且需要执行`composer dumpautoload`来生成classmap