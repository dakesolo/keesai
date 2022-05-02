# 简介
Keesai是基于hyperf+redis queue+mysql saga模式的高性能分布式事务框架
### 名词
* 事务`transaction`：比如下单，包含一系列行为
* 行为`behavior`：一个事务包含若干个行为，可以这么认为，一个行为由一个微服务给出，包含执行和补偿
* 执行`execute`: 行为正向执行
* 补偿`compensate`：行为反向执行
### 特色
* 支持异步
* 代码无侵入
### 状态
#### transaction
* pending：初始状态
* success：成功
* failed：失败
#### behavior
* pending：初始状态
* success：成功
* failing：执行失败，未补偿
* failed：执行失败，已补偿
![image.png](https://s1.ax1x.com/2022/05/02/OPUzqI.png)
# 使用
## 安装
支持docker，请参考hyperf
## 举例说明
![image.png](https://s1.ax1x.com/2022/05/02/OPU7a6.png)
* 图中描述的是一个订单事务
* BFF为事务组织方，可以将其当成一个微服务
* Order,Product,User,Coupon为四个微服务
* Keesai作为独立的服务，并未与任何服务产生耦合
* 用户发起一个`submitOrder`事务
* BFF将该事务的各种行为`createOrder`、`debitMoney`、`debitProduct`、`exchangeCoupon`及事务`submitOrder`，通过Keesai提供的`提交事务清单
  `、`提交行为清单`提交过去，具体格式，参考接口
* BFF向用户返回`transactionId`
* 以上行为，全部为串行，至此，BFF工作完成，Keesai工作开始
* 服务方可通过Keesai的mysql跟踪状态及最终结果
## 开发原则
* 一个事务里要求的所有行为返回结果对其他行为都是透明的，共享`transaction`
>比如扣库存，库存服务方只能得到当前transactionId，而不能得到订单号，如果想要订单号，那么后期可以到调取事务清单及行为清单
* 各个服务方只需要提供补偿动作、错误状态、成功状态，要求状态格式统一
>如果第三方服务不提供补偿服务，需要自己再建个服务，然后自己实现补偿动作，该动作记录手工该处理的异常
* 需要在各个微服务表中多加一个`transaction_id`，以便支持补偿、事务查询
## 接口
### 提交事务清单
>该接口一般业务发起方提供
##### method
post
##### path
_transaction/submitTransaction_
##### param
```json
{
  "name": "submitOrder",
  "expire": 10
}
```
| 名称            | 位置   | 类型       | 必选  | 说明       |
|---------------|------|----------|-----|----------|
| name          | body | string   | 是   | 事务名称，预定义 |
| expire        | body | integer  | 是   | 过期时间，单位s |
##### response
```json
{
  "transactionId": "550e8400-e29b-41d4-a716-446655440000"
}
```
### 提交行为清单
>该提交如果失败(服务方超时，服务方返回错误)，本地必须处理，要么本地补偿，要么最终成功提交
##### method
post
##### path
_transaction/submitBehavior_
##### param
```json
[
  {
    "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725",
    "consistency": "compensate",
    "name": "createOrder",
    "execute": [
      "GET",
      "http://192.168.175.128:9501/order/createOrder",
      {
        "query": {
          "productId": "123123",
          "number": "30",
          "userId": 90
        },
        "headers": {
          "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725"
        }
      }
    ],
    "compensate": [
      "GET",
      "http://192.168.175.128:9501/order/createOrderCompensate",
      {
        "query": {
          "productId": "123123",
          "number": "30",
          "userId": 90
        },
        "headers": {
          "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725"
        }
      }
    ],
    "retry": 0,
    "retryMax": 0
  },
  {
    "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725",
    "consistency": "compensate",
    "name": "debitProduct",
    "execute": [
      "GET",
      "http://192.168.175.128:9501/product/debitProduct",
      {
        "query": {
          "productId": "123123"
        },
        "headers": {
          "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725"
        }
      }
    ],
    "compensate": [
      "GET",
      "http://192.168.175.128:9501/product/debitProductCompensate",
      {
        "query": {
          "productId": "123123"
        },
        "headers": {
          "transactionId": "f2e65fc5-8177-4794-abbc-77cac5716725"
        }
      }
    ],
    "retry": 0,
    "retryMax": 0
  }
]
```
| 名称            | 位置   | 类型       | 必选  | 说明                               |
|---------------|------|----------|-----|----------------------------------|
| transactionId | body | string   | 是   | 事务id                             |
| name          | body | string   | 是   | 行为名称                             |
| consistency   | body | string   | 是   | 一致性描述：compensate,execute         |
| execute       | body | [object] | 是   | 正向操作，等效于http触发动作，值格式参考 Guzzle    |
| compensate    | body | [object] | 是   | 补偿操作，等效于http触发动作，值格式参考 Guzzle    |
| retry         | body | integer  | 是   | 重试间隔时长，单位s，consistency为execute有效 |
| retryMax      | body | integer  | 是   | 重试最大次数，consistency为execute有效     |