<?php
namespace Xwork\xmap\idgenerator;

// 用数据库来实现
use Xwork\xmap\BeanFinder;

class IDGeneratorByDb implements IDGeneratorBase
{

    private $queue = array();

    // 获取下一个id
    public function getNextID () {
        if (empty($this->queue)) {
            self::preLoad();
        }

        return array_shift($this->queue);
    }

    // 预取
    public function preLoad ($size = 10) {
        if ($this->queue && count($this->queue) >= $size) {
            return true;
        }

        $dbExecuter = BeanFinder::getDbExecuter();
        $dbExecuter->saveNeedRwSplit();
        $dbExecuter->unNeedRwSplit();

        BeanFinder::getDbExecuter()->executeNoQuery("update idgenerator set nextid=LAST_INSERT_ID(nextid+$size)");
        $nextId = BeanFinder::getDbExecuter()->queryValue("select LAST_INSERT_ID() as nextid");

        $dbExecuter->restoreRwSplit();

        for ($i = $size; $i > 0; $i --) {
            $this->queue[] = $nextId - $i;
        }

        return true;
    }
}

/*
 * 当旧系统的代码不能支持全局唯一id，而采用自增id时，可以通过以下触发器来实现
 * 要求，必须有id,createtime,updatetime 字段
 * 同理也可以写一个trig_before_update_aaa 来实现 updatetime 和 version字段的维护
 * sjp : 实践证明这种方法行不通,会导致数据错误,仅当学习触发器使用方法吧

CREATE TABLE `aaa` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `version` int(11) NOT NULL DEFAULT '1',
          `createtime` datetime NOT NULL,
          `updatetime` datetime NOT NULL DEFAULT '2010-01-01 00:00:00',
          `name` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=20000016 DEFAULT CHARSET=utf8

---------------------------
DELIMITER $$;

DROP TRIGGER `groupon_dev`.`trig_before_insert_aaa`$$

create trigger `trig_before_insert_aaa` BEFORE INSERT on `aaa`
for each row begin
if NEW.id < 1 then
update idgenerator set nextid=LAST_INSERT_ID(nextid+2);
set NEW.id=LAST_INSERT_ID()-2;
set NEW.createtime = now();
set NEW.updatetime = now();
end if;
end;
$$

DELIMITER ;$$

---------------------------

DELIMITER $$;

DROP TRIGGER `groupon_dev`.`trig_before_update_aaa`$$

create trigger `trig_before_update_aaa` BEFORE UPDATE on `aaa`
for each row begin
if NEW.version <= OLD.version then
set NEW.version = OLD.version+1;
set NEW.updatetime = now();
end if;
end;
$$

DELIMITER ;$$

 */