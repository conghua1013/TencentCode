<?php

/* @var $this yii\web\View */

$this->title = '韩家-小树苗成长日记';
?>
<h1 id="toc_0">基于工作流平台禧鹊开发新审批流程</h1>

<h2 id="toc_1">名词解释</h2>

<p>流程模板: 审批按照什么流程进行流转的配置文件<br/>
    详情模板: 审批详情页面要展示的页面配置文件配合配合 workflow-web 使用</p>

<h2 id="toc_2">背景</h2>

<p>制定一个新的流程,是所涉及的业务平台参与方研发一起商讨确定的.不是单独一方完成.(这是目前的前提)</p>

<h2 id="toc_3">一、步骤</h2>

<ul>
    <li>创建流程模板</li>
    <li>导出流程模板</li>
    <li>将流程模板导入数据库</li>
    <li>新增调用平台应用信息</li>
    <li>新增应用和流程模板授权</li>
    <li>新增详情模板配置</li>
    <li>提交审批</li>
    <li>拉取待办</li>
    <li>输出审批列表</li>
    <li>审批通过/驳回/取消</li>
    <li>notify通知审批结果(通过/驳回/取消)</li>
    <li>处理数据</li>
</ul>

<h2 id="toc_4">二、创建流程模板</h2>

<h3 id="toc_5">模板配置工具准备</h3>

<p>工作流模板创建工具有很多,就不一一列举了.提供一个我在使用的工具.flowable公司提供的.<br/>
    dockerImage: <code>flowable/all-in-one:6.4.2</code></p>

<p>docker的使用方法请查阅相关资料,不再赘述.<br/>
    镜像的启动方式看一下文档即可;<a href="https://github.com/flowable/flowable-engine/tree/master/docker/all-in-one">https://github.com/flowable/flowable-engine/tree/master/docker/all-in-one</a></p>

<p>不要问我docker怎么用,问一下度娘比我更专业.</p>

<h3 id="toc_6">配置流程模板</h3>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766445970729.jpg" alt="" style="width:1272px;"/></p>

<p>每个任务也必须配置分配用户,指定候选组,可以配置多个.<br/>
    用户组字段规则为, 平台的<code>app_id _ 职位标识或其他标识</code><br/>
    如:禧鹊提交审批 100001_BD 审批节点 100001_22 (职位id) <br/>
    开放平台提交审批 100002_ISV </p>

<p>每个任务必须配置执行监听器,这是规范,为以后流程增加统一处理规则使用.三个事件(start/end/take)委托表达式统一配置为<code>${xyExecutionListener}</code></p>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766447414494.jpg" alt="" style="width:991px;"/><br/>
    <img src="http://troy-pub.yanzinet.cn/mweb/15766447808028.jpg" alt="" style="width:995px;"/></p>

<p>用到排他网关的流程,必须设置分支的流条件的条件表达式,参数统一为<code>${examineStatus == &#39;Reject&#39;}</code> 和 <code>${examineStatus == &#39;Pass&#39;}</code> <em>注意大小写</em></p>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766450298728.jpg" alt="" style="width:981px;"/></p>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766450455479.jpg" alt="" style="width:1013px;"/></p>

<h2 id="toc_7">三、下载流程模板</h2>

<p>配置完流程模板后,将其下载为xml文件. 流程文件扩展名必须是<code>.bpmn20.xml</code></p>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766451044315.jpg" alt="" style="width:1268px;"/></p>

<h2 id="toc_8">四、将流程模板导入数据库</h2>

<p>用curl请求或postman</p>

<pre class="line-numbers"><code class="language-shell">curl --request POST \
  --url http://t1514.bigmiddle-workflow-api.yunzong:12630/api/v1/bpmn/import \
  --header &#39;Postman-Token: 42204fc8-e487-41c0-9d30-e6931b0475e4&#39; \
  --header &#39;cache-control: no-cache&#39; \
  --header &#39;content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW&#39; \
  --form key=examine_process_isv_app \
  --form &#39;name=应用授权审批&#39; \
  --form &#39;bpmn=@/Users/hanshunchuang/Downloads/isv应用授权审批.bpmn20.xml&#39;
</code></pre>

<p><img src="http://troy-pub.yanzinet.cn/mweb/15766454091239.jpg" alt="" style="width:971px;"/></p>

<h2 id="toc_9">五、新增调用平台应用信息</h2>

<p>已开放平台为例<br/>
    当前版本notify_url和callback_url未使用,后期版本扩展使用</p>

<p>新增前请确认双方秘钥.</p>

<pre class="line-numbers"><code class="language-sql">INSERT INTO flow_app (app_id, app_name, status, notify_url, callback_url, app_pub_key, remark, create_time, update_time)
VALUES (100002, &#39;开放平台&#39;, 1, &#39;http://open.com&#39;, &#39;http://open.com&#39;,
        &#39;MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0B2G4JDWg33p4AJ4W4RLSBDMF2Oq0AMqJeoNxzGCzUhfS0Tk4ylOwROv7e16VR/MKiOSdONW3e/+KMt8aFB0Gkm70eVmGi63Jwep05ful19dsh5hHwd8vX5FLMUpKvkMd4pJKSm2kXlvvpdeQ4GYbqf/zpg4FP1is/lX7kz/NmS57PnbSr8UkjofF87OAwmZ2V+Cq2zwGXCkIh5hziCaO6vqgsMR9LoUwNTfYeE82mXGlRUVJuEh2lb/xd+NNKrIcG3tgpDYFaK+cSi3qYtGNmR7su8HQ0wsQIDAQAB&#39;,
        &#39;开放平台&#39;, &#39;1970-01-01 01:00:00&#39;, &#39;1970-01-01 01:00:00&#39;);
</code></pre>

<h2 id="toc_10">六、新增应用和流程模板授权</h2>

<p>已开放平台应用授权审批为例<br/>
    将该流程参与的平台统一增加授权<br/>
    应用授权审批参与平台为开放平台和禧鹊</p>

<pre class="line-numbers"><code class="language-sql">INSERT INTO flow_app_process (process_key, status, app_id, create_time, update_time)
VALUES (&#39;examine_process_isv_app&#39;, 1, 100002, &#39;2019-12-04 09:44:11&#39;, &#39;2019-12-04 09:44:15&#39;);
INSERT INTO flow_app_process (process_key, status, app_id, create_time, update_time)
VALUES (&#39;examine_process_isv_app&#39;, 1, 100001, &#39;2019-12-04 09:44:49&#39;, &#39;2019-12-04 09:44:51&#39;);
</code></pre>

<h2 id="toc_11">七、新增详情模板配置</h2>

<p>已开放平台应用授权审批为例</p>

<pre class="line-numbers"><code class="language-sql">INSERT INTO flow_template (template, template_name, create_time, update_time, process_key, is_delete)
VALUES (&#39;[{&quot;patchs&quot;:[{&quot;title&quot;:&quot;ISV公司信息&quot;,&quot;type&quot;:&quot;table&quot;,&quot;fields&quot;:{&quot;body&quot;:[[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;公司名称：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{companyName}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;管理员账号（禧云开放平台）：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;components&quot;,&quot;components&quot;:&quot;sensitive&quot;,&quot;value&quot;:&quot;{{account}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}]]}},{&quot;title&quot;:&quot;申请权限&quot;,&quot;type&quot;:&quot;table&quot;,&quot;fields&quot;:{&quot;body&quot;:[[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;应用名称：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{appName}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;应用描述：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{appRemark}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;应用APPID：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{appId}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;服务名称：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{serviceName}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;服务描述：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{serviceRemark}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}]]}},{&quot;title&quot;:&quot;商户信息&quot;,&quot;type&quot;:&quot;table&quot;,&quot;fields&quot;:{&quot;body&quot;:[[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;商户名称：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{merchantName}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;商户主账号（禧云商家中心）：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{merchantMainAccount}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;商户编号：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;{{merchantCode}}&quot;,&quot;options&quot;:{&quot;colspan&quot;:1}}],[{&quot;type&quot;:&quot;text&quot;,&quot;value&quot;:&quot;商户授权协议（照片）：&quot;,&quot;options&quot;:{&quot;colspan&quot;:1,&quot;style&quot;:{&quot;fontWeight&quot;:&quot;bold&quot;}}},{&quot;type&quot;:&quot;components&quot;,&quot;components&quot;:&quot;v-viewer&quot;,&quot;value&quot;:[{&quot;src&quot;:&quot;{{protocolPicUrl}}&quot;,&quot;desc&quot;:&quot;{{createTime}}&quot;,&quot;name&quot;:&quot;{{picName}}&quot;}],&quot;options&quot;:{&quot;colspan&quot;:1}}]]}}]}]&#39;,
        &#39;ISV应用授权审批&#39;, &#39;2019-12-04 16:57:08&#39;, &#39;2019-12-04 16:57:11&#39;, &#39;examine_process_isv_app&#39;, 0);
</code></pre>

<p>该模板即可通过接口使前端生成页面<br/>
    <img src="http://troy-pub.yanzinet.cn/mweb/15766459301800.jpg" alt="" style="width:981px;"/></p>

<p>前端提供可选组件参考: <a href="https://xiyun-international.github.io/xy/ant-design-ui/flow-detail.html">https://xiyun-international.github.io/xy/ant-design-ui/flow-detail.html</a></p>

<p>前端校验地址: <a href="http://ds.bigmiddle-workflow-web.yunzong:12635/#/test">http://ds.bigmiddle-workflow-web.yunzong:12635/#/test</a></p>

<p>模板配置json结构</p>

<pre class="line-numbers"><code class="language-javascript">[
  {
    &quot;patchs&quot;: [
      {
        &quot;title&quot;: &quot;ISV公司信息&quot;,
        &quot;type&quot;: &quot;table&quot;,
        &quot;fields&quot;: {
          &quot;body&quot;: [
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;公司名称：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{companyName}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;管理员账号（禧云开放平台）：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;components&quot;,
                &quot;components&quot;: &quot;sensitive&quot;,
                &quot;value&quot;: &quot;{{account}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ]
          ]
        }
      },
      {
        &quot;title&quot;: &quot;申请权限&quot;,
        &quot;type&quot;: &quot;table&quot;,
        &quot;fields&quot;: {
          &quot;body&quot;: [
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;应用名称：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{appName}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;应用描述：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{appRemark}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;应用APPID：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{appId}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;服务名称：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{serviceName}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;服务描述：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{serviceRemark}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ]
          ]
        }
      },
      {
        &quot;title&quot;: &quot;商户信息&quot;,
        &quot;type&quot;: &quot;table&quot;,
        &quot;fields&quot;: {
          &quot;body&quot;: [
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;商户名称：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{merchantName}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;商户主账号（禧云商家中心）：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{merchantMainAccount}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;商户编号：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;{{merchantCode}}&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ],
            [
              {
                &quot;type&quot;: &quot;text&quot;,
                &quot;value&quot;: &quot;商户授权协议（照片）：&quot;,
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1,
                  &quot;style&quot;: {
                    &quot;fontWeight&quot;: &quot;bold&quot;
                  }
                }
              },
              {
                &quot;type&quot;: &quot;components&quot;,
                &quot;components&quot;: &quot;v-viewer&quot;,
                &quot;value&quot;: [
                  {
                    &quot;src&quot;: &quot;{{protocolPicUrl}}&quot;,
                    &quot;desc&quot;: &quot;{{createTime}}&quot;,
                    &quot;name&quot;: &quot;{{picName}}&quot;
                  }
                ],
                &quot;options&quot;: {
                  &quot;colspan&quot;: 1
                }
              }
            ]
          ]
        }
      }
    ]
  }
]
</code></pre>

<p>实体数据结构 提交审批接口initData字段数据</p>

<pre class="line-numbers"><code class="language-javascript">{
  &quot;appId&quot;: 10181930,
  &quot;appName&quot;: &quot;aaa200&quot;,
  &quot;appRemark&quot;: &quot;aaa200&quot;,
  &quot;companyName&quot;: &quot;测试010&quot;,
  &quot;createTime&quot;: &quot;2019-12-06&quot;,
  &quot;id&quot;: 10197914,
  &quot;merchantCode&quot;: &quot;100103&quot;,
  &quot;merchantMainAccount&quot;: &quot;13581651932&quot;,
  &quot;merchantName&quot;: &quot;陕西服装工程学院&quot;,
  &quot;picName&quot;: &quot;111.png&quot;,
  &quot;protocolPicUrl&quot;: &quot;http://yunzongtest.oss-cn-beijing.aliyuncs.com/2019/12/06/16/809481575621984.png&quot;,
  &quot;serviceId&quot;: 10005201,
  &quot;serviceName&quot;: &quot;支付服务&quot;,
  &quot;serviceRemark&quot;: &quot;网关支付服务列表&quot;,
  &quot;status&quot;: 0
}
</code></pre>

<p>调用工作流平台接口获取完整前端需要的数据渲染成页面.(前端工程需支持workflow-web组件)</p>

<pre class="line-numbers"><code class="language-shell">curl --request POST \
  --url http://localhost:12630/api/v1/bpmn/examineInfoPage \
  --header &#39;Content-Type: application/x-www-form-urlencoded&#39; \
  --header &#39;Postman-Token: 7155db8b-cb81-424c-b3fb-b16447fc5bc7&#39; \
  --header &#39;cache-control: no-cache&#39; \
  --data &#39;processInstanceId=54f0f125-0a96-11ea-912e-4a650e2916ee&amp;gCode=12&amp;appId=100001&amp;sign=fFflfxLeqfyfWcfZz37Zu6lxWNfAnzWA9m%2BgJKUOORvyfZi5Hmhqy3wKkbxR3kSwvJOUDIvre6Uz6XbasYj%2FMJTiV3IYcyxTHXSht2Cf5ZCO6rEEnKa96JHJsLw6Fg%2BtE4bhIFsLHiLrFnT8CeW7U4IIZtFGza1CgJVL%2Ff%2FejwBazXVsZ8RuTVnIrelT8gI1fDt0s8BakX%2BXgs4IjQgOmOTZ1mERX%2Fu49YLldUY56NL0jAxZAAGfsUWlTlDFz0njZt1zRHZrDuy3%2BuQ%2F8Q4KMHTnD4Cdol3TSpsvKqy%2FUqKB48lXmVE9IvPMl2XUtDG1k6TfYB3lJthhNycM3HbGmw%3D%3D&#39;
</code></pre>

<p>提供详情页面有两种方式:<br/>
    1.iframe嵌入workflow-web页面.嵌入地址 <br/>
    <code>http://ts.xy-workflow-engine-frontend.yunzong:12545/#/detail?processInstanceId=57e91fe4-0c3f-11ea-90e9-0242ac11000d&amp;appId=100001&amp;gCode=11&amp;sign=eIqFcS21Vt8%2FdA1I6elkPtjdTjRyPvxmzQ%2FJSCWQDusnYFrOZqJgK%2BN9Z4WkouCVZxs3ueJJkrgXFOXdkK2Y3Ata0O8GDMS5kMDsMyiOfQszwRX%2BswHjVCAcg5u%2Fg8theLvL8ZzGBWE1EVzJX5cUuVyr531gC24bWTA9MtJz%2BuohfSme8%2FdSt8nEXWwzDexMtgXOrQG96hQ2qsB62qF3j9fCzpLeCfzXsUIVuPAP1GHm75bkT5nlgJL5GPd3wXFg6RLntof%2FovIfey%2FK%2FStjcq9HC1f3xEioMmeyRYnTpYE4eCZN5ExVq86FYw2QsvNwK432Aiy0CPe2%2FRBczqr%2BuA%3D%3D</code> 链接中的参数由服务端生成<br/>
    2.前端工程引入workflow-web组件.服务端将工作流接口输出数据转发给前端即可.</p>

<p>模板和实体数据进行替换配置规则语法参考: <a href="http://jknack.github.io/handlebars.java/gettingStarted.html">http://jknack.github.io/handlebars.java/gettingStarted.html</a></p>

<h2 id="toc_12">八、提交审批</h2>

<p>提交审批<br/>
    uCode: 平台各自的操作人员标识<br/>
    gCode: 流程模板配置时指定的候选组</p>

<pre class="line-numbers"><code class="language-shell">curl --request POST \
  --url http://localhost:13001/api/v1/bpmn/startTask \
  --header &#39;Postman-Token: 8dbf29ce-d84c-4ccc-bffe-cd0e31f90a58&#39; \
  --header &#39;cache-control: no-cache&#39; \
  --header &#39;content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW&#39; \
  --form uCode=2 \
  --form gCode=ISV \
  --form &#39;initData={&quot;account&quot;:&quot;18612902902&quot;,&quot;appId&quot;:910032,&quot;appName&quot;:&quot;应用名称&quot;,&quot;appRemark&quot;:&quot;应用备注&quot;,&quot;companyName&quot;:&quot;公司名称&quot;,&quot;createTime&quot;:&quot;2019-12-04&quot;,&quot;picName&quot;:&quot;图片名称&quot;,&quot;id&quot;:1000,&quot;protocolPicUrl&quot;:&quot;图片url&quot;,&quot;serviceId&quot;:100111,&quot;serviceName&quot;:&quot;数据推送服务&quot;,&quot;serviceRemark&quot;:&quot;推送数据用的&quot;,&quot;status&quot;:1,&quot;merchantCode&quot;:&quot;10010&quot;,&quot;merchantName&quot;:&quot;商户名称&quot;}&#39; \
  --form processKey=examine_process_isv_app \
  --form appId=100002
</code></pre>

<p><strong><em>提交工作流平台成功后需建processId流程实例id与审批实体对象数据建立关系表.(后续跟据流程实例id获取审批实体对象数据时会用到)</em></strong></p>

<p>禧鹊的关系表是<code>qy_examine_commit_job</code>.存储禧鹊所有作为 <u>提交审批角色</u> 的关系数据.(强弱电方案审批)</p>

<p>开放平台的关系表(应用授权审批)</p>

<h2 id="toc_13">九、拉取待办</h2>

<p>拉取待办脚本 <code>./yii work-flow-get-todo/index</code></p>

<p>php端:<br/>
    <code>\diningData\services\examine\base\WorkFlowProcessEnum</code> 新增枚举</p>

<p>新增业务service类必须继承,业务数据保存自行处理<code>\diningData\services\workFlow\examineClient\ExamineObjectInfo</code></p>

<p>修改<code>\diningData\services\workFlow\TaskTodoService::getObjectIdByProcessKey</code></p>

<p>获取待办记录需要根据工作流平台获取的processId从审批提交平台获取审批实体数据,字段根据审批业务需要指定.</p>

<blockquote>
    <p>如果是禧鹊提交的审批,禧鹊处理,只需要根据processId从表<code>qy_examine_commit_job</code>获取审批类型process_key,对象id <code>object_id</code> 关联业务数据表获取审批实体数据</p>
</blockquote>

<p>以应用授权审批为例:</p>

<ol>
    <li>从工作流平台获取待办数据(包含流程实例id,流程类型,任务id等字段)</li>
    <li>根据流程实例id从开放平台获取审批实体数据(公司名称, 主键id 等字段)</li>
    <li>组装相关数据</li>
    <li>将待办数据写入<code>qy_task_todo</code>表(待办公共表)</li>
    <li>将审批实体数据写入到<code>qy_wf_bs_isv_app</code>表 (每个审批业务独有的业务数据表)</li>
    <li>写入操作日志</li>
</ol>

<p>具体流程参考下面的交互流程图:</p>

<pre class="line-numbers"><code class="language-shell">curl --request POST \
  --url http://localhost:13001/api/v1/bpmn/getTaskTodo \
  --header &#39;Content-Type: application/x-www-form-urlencoded&#39; \
  --header &#39;Postman-Token: e9f24303-5a9a-4254-b6a2-91dd5710a525&#39; \
  --header &#39;cache-control: no-cache&#39; \
  --header &#39;content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW&#39; \
  --form groupId=gProjectExamine \
  --form appId=100001
</code></pre>

<h2 id="toc_14">十、获取审批列表</h2>

<p>审批列表Java端根据业务需求连表生成,参考<code>net.xiyun.middle.home.service.impl.workflow.ExamineIsvAppServiceImpl#getList</code></p>

<h2 id="toc_15">十一、审批通过/驳回/取消</h2>

<p>Java端<br/>
    增加枚举<br/>
    <code>net.xiyun.middle.home.dao.enums.ProcessKeyEnum</code><br/>
    业务实现类实现接口<code>net.xiyun.middle.home.service.workflow.ExamineAfterService</code><br/>
    修改方法<code>net.xiyun.middle.home.service.impl.workflow.ExamineRouterServiceImpl#getAfterService</code>增加接口<code>net.xiyun.middle.home.service.workflow.ExamineAfterService</code>实现类</p>

<p>暂时未涉及审批取消</p>

<blockquote>
    <p>这期抽象的还不够彻底,后续需求开发过程中再做优化</p>
</blockquote>

<h2 id="toc_16">十二、通知审批结果(通过/驳回/取消)</h2>

<p>监听队列<code>wf.event.notify</code></p>

<pre class="line-numbers"><code class="language-javascript">{
    &quot;eventType&quot;: &quot;examine_result&quot;,
    &quot;processId&quot;: &quot;0&quot;,
    &quot;processKey&quot;: &quot;&quot;,
    &quot;desc&quot;: &quot;&quot;,
    &quot;status&quot;: &quot;&quot; // pass || reject
    &quot;eventTime&quot;: &quot;&quot;
}
</code></pre>

<p><em>如果禧鹊操作审批结果,禧鹊可以监听队列异步处理结果,也可以操作时同步处理后续业务</em></p>

<p>根据processId处理实际业务场景需要处理的数据.</p>

<h2 id="toc_17">十三、处理数据</h2>

<p>根据实际业务场景处理业务数据</p>

<h2 id="toc_18">十四、禧鹊开放平台应用授权审批交互流程</h2>

<p><img src="http://troy-pub.yanzinet.cn/mweb/%E5%BC%80%E6%94%BE%E5%B9%B3%E5%8F%B0%E5%BA%94%E7%94%A8%E5%AE%A1%E6%89%B9%E6%B5%81%E7%A8%8B.jpg" alt="开放平台应用审批流程"/></p>

<p>Copyright © Beijing yanzinet Co., Ltd., All Rights Reserved <a href="http://www.beian.miit.gov.cn">京ICP备18002925号-2</a></p>

