# 简介
Keesai是基于hyperf+redis+mysql saga模式的高性能分布式事务框架
### 名词
* 事务`transaction`：比如下单，包含一系列行为
* 行为`behavior`：一个事务包含若干个行为，可以这么认为，一个行为由一个微服务给出，包含执行和补偿
* 执行`execute`: 行为正向执行
* 补偿`compensate`：行为反向执行
### 架构
* 基于消息触发的高性能异步架构
* 可观测的清单型架构
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
## 举例说明
![image.png](https://s1.ax1x.com/2022/05/02/OPU7a6.png)
* 图中描述的是一个订单事务
* BFF为事务组织方，可以将其当成一个微服务
* Order,Product,User,Coupon为四个微服务
* Keesai作为独立的服务，并未与任何服务产生耦合
* 用户发起一个`submitOrder`事务
* BFF将该事务的各种行为`createOrder`、`debitMoney`、`debitProduct`、`exchangeCoupon`及事务`submitOrder`，通过Keesai提供的两个接口提交过去
* BFF向用户返回`transactionId`
* 以上行为，全部为串行，至此，BFF工作完成，Keesai工作开始
* 服务方可通过Keesai的mysql跟踪状态及最终结果
## 开发原则
* 一个事务里要求的所有行为返回结果对其他行为都是透明的，共享`transaction`
>比如扣库存，库存服务方只能得到当前transactionId，而不能得到订单号，如果想要订单号，那么后期可以到调取事务清单及行为清单
* 各个服务方只需要提供补偿动作、错误状态、成功状态，要求状态格式统一
>如果第三方服务不提供补偿服务，需要自己再建个服务，然后自己实现补偿动作，该动作记录手工该处理的异常
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
  "expire": 10,
  "action": [
    "createOrder",
    "debitMoney",
    "debitProduct",
    "exchangeCoupon"
  ]
}
```
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
    "retry_max": 0
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
    "retry_max": 0
  }
]
```


# 对象
#### 事务清单 TD(transaction detail)
```json
{
  "transactionId": "091838e12-e29b-41d4-a716-446655447777",
  "name": "submitOrder",
  "status": "pending",
  "behaviors": [
    "createOrder",
    "debitMoney",
    "debitProduct",
    "exchangeCoupon"
  ],
  "expire": 30000,
  "time": "2022-03-10 10:00:01"
}
```
| 名称            | 位置   | 类型       | 必选  | 说明                        |
|---------------|------|----------|-----|---------------------------|
| transactionId | body | integer  | 是   | 事务id                      |
| name          | body | string   | 是   | 事务名称，预定义                  |
| status        | body | string   | 是   | 状态：pending,success,failed |
| expire        | body | integer  | 是   | 过期时间，单位毫秒                 |
| time          | body | datetime | 是   | 发生时间，待优化                  |
| behaviors     | body | [string] | 是   | 行为清单，预定义                  |

#### 行为清单 BD(behavior detail)
```json
{
  "transactionId": "091838e12-e29b-41d4-a716-446655447777",
  "name": "createOrder",
  "consistency": "compensate",
  "status": "ok",
  "time":"2022-03-10 10:00:01",
  "action": [
    {
      "name": "createOrder",
      "execute": {
        "path": "http://domain/createOrder",
        "header": {},
        "param": {
          "transactionId": 1001,
          "userId": 89,
          "productId": 12188
        }
      },
      "compensate": {
        "path": "http://domain/removeOrder",
        "header": {},
        "param": {
          "transactionId": 1001,
          "orderId": 20090
        }
      }
    }
  ]
}
```
| 名称                | 位置   | 类型       | 必选  | 说明                        |
|-------------------|------|----------|-----|---------------------------|
| transactionId     | body | string   | 是   | 事务id                      |
| behaviorId        | body | string   | 是   | 行为id                      |
| name              | body | string   | 是   | 行为名称                      |
| consistency       | body | string   | 是   | 一致性描述：compensate,execute  |
| status            | body | string   | 是   | 状态：success,failing,failed |
| time              | body | string   | 是   | 发生时间，待优化                  |
| action            | body | [object] | 是   | 动作                        |
| » name            | body | string   | 是   | 动作名称                      |
| » execute         | body | object   | 是   | 已执行，等效于http触发动作           |
| »» path           | body | string   | 是   | 路径                        |
| »» header         | body | object   | 是   | 头部                        |
| »»» transactionId | body | string   | 是   | 分布式事务Id                   |
| »» param          | body | object   | 否   | 示例参数                      |
| »»» userId        | body | integer  | 是   | 示例值                       |
| »»» productId     | body | integer  | 是   | 示例值                       |
| » compensate      | body | object   | 是   | 补偿名称，与execute互补           |
| »» path           | body | string   | 是   | 路径                        |
| »» header         | body | object   | 是   | 头部                        |
| »»» transactionId | body | string   | 是   | 分布式事务Id                   |
| »» param          | body | object   | 是   | 示例参数                      |
| »»» orderId       | body | string   | 是   | 示例值                       |