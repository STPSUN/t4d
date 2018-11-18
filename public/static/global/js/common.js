$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function openBarWinParent(title, width, height, url, callback, btn) {
    if (typeof (btn) == 'undefined') {
        btn = ['保存', '保存并关闭', '取消'];
        flag = false;
    }
    var flag = true;
    if (btn.length > 2)
        flag = false;
    title = '<i class="icon wb-order"></i>' + title;
    parent.layer.open({
        type: 2,
        title: title,
        btn: btn,
        area: [width + 'px', height + 'px'],
        maxmin: false,
        //skin: 'layui-layer-rim', //加上边框
        content: url,
        btn2: function (index) {
            if (flag) {
                this.close(index);//编辑时 该按钮是取消的功能
                return;
            }
            var iframe = window.frames["layui-layer-iframe" + index];
            if (!iframe)
                iframe = window.parent.frames["layui-layer-iframe" + index];
            if (typeof (iframe.saveData) != "undefined") {
                iframe.saveData.call(this, callback, index, true);
            } else if (typeof (callback) != "undefined") {
                callback(index);
                this.close(index); //一般设定yes回调，必须进行手工关闭
            } else {
                this.close(index); //一般设定yes回调，必须进行手工关闭
            }
            return false;
        },
        yes: function (index, layero) {
            var iframe = window.frames["layui-layer-iframe" + index];
            if (!iframe)
                iframe = window.parent.frames["layui-layer-iframe" + index];
            if (typeof (iframe.saveData) != "undefined") {
                iframe.saveData.call(this, callback, index, flag);
            } else if (typeof (callback) != "undefined" && callback != "") {
                callback(index);
                this.close(index); //一般设定yes回调，必须进行手工关闭
            } else {
                this.close(index); //一般设定yes回调，必须进行手工关闭
            }
        }
    });
}
function openBarWin(title, width, height, url, callback, btn) {
    if (typeof (btn) == 'undefined') {
        btn = ['保存', '保存并关闭', '取消'];
        flag = false;
    }
    var flag = true;
    if (btn.length > 2)
        flag = false;
    title = '<i class="icon wb-order"></i>' + title;
    layer.open({
        type: 2,
        title: title,
        btn: btn,
        area: [width + 'px', height + 'px'],
        maxmin: false,
        //skin: 'layui-layer-rim', //加上边框
        content: url,
        btn2: function (index) {
            if (flag) {
                this.close(index);//编辑时 该按钮是取消的功能
                return;
            }
            var iframe = window.frames["layui-layer-iframe" + index];
            if (!iframe)
                iframe = window.parent.frames["layui-layer-iframe" + index];
            if (typeof (iframe.saveData) != "undefined") {
                iframe.saveData.call(this, callback, index, true);
            } else if (typeof (callback) != "undefined") {
                callback(index);
                this.close(index); //一般设定yes回调，必须进行手工关闭
            } else {
                this.close(index); //一般设定yes回调，必须进行手工关闭
            }
            return false;
        },
        yes: function (index, layero) {
            var iframe = window.frames["layui-layer-iframe" + index];
            if (!iframe)
                iframe = window.parent.frames["layui-layer-iframe" + index];
            if (typeof (iframe.saveData) != "undefined") {
                iframe.saveData.call(this, callback, index, flag);
            } else if (typeof (callback) != "undefined" && callback != "") {
                callback(index);
                this.close(index); //一般设定yes回调，必须进行手工关闭
            } else {
                this.close(index); //一般设定yes回调，必须进行手工关闭
            }
        }
    });
}

function openWin(title, width, height, url, callback) {
    layer.open({
        type: 2,
        title: title,
        area: [width + 'px', height + 'px'],
        maxmin: false,
        //skin: 'layui-layer-rim', //加上边框
        content: url,
        yes: function (index) {
            if (callback) {
                callback(index);
            }
            this.close(index); //一般设定yes回调，必须进行手工关闭
        }
    });
}

function delData(url, msg) {
    if (msg == null || msg == "")
        msg = "确认要删除吗？";
    layer.confirm(msg, {icon: 3}, function () {
        location.href = url;
    });
}

function alert(msg, callback) {
    layer.alert(msg, {icon: 6}, function (index) {
        this.close(index);
        if (typeof (callback) != "undefined")
            callback();
    });
}
function confirm(msg, ok, cancel) {
    layer.confirm(msg, {icon: 3}, function (index) {
        if (ok)
            ok(index);
        this.close(index);
    }, cancel);
}

function showLoading(msg) {
    if (msg == null || msg == "")
        msg = "加载中...";
    layer.msg(msg, {icon: 16, time: 0, shade: 0.01});
}

function hideLoading() {
    $(".layui-layer-shade").hide();
    $(".layui-layer").hide();
}
function msg(text, time) {
    if (time == null)
        time = 1000;
    layer.msg(text, {time: time});
}


function parseToDate(value) {
    if (value == null || value == '') {
        return undefined;
    }
    var dt;
    if (value instanceof Date) {
        dt = value;
    } else {
        if (!isNaN(value)) {
            dt = new Date(parseInt(value) * 1000);
            //dt = new Date(value);
        } else if (value.indexOf('/Date') > -1) {
            value = value.replace(/\/Date(−?\d+)\//, '$1');
            dt = new Date();
            dt.setTime(value);
        } else if (value.indexOf('/') > -1) {
            dt = new Date(Date.parse(value.replace(/-/g, '/')));
        } else {
            dt = new Date(value);
        }
    }
    return dt;
}

//为Date类型拓展一个format方法，用于格式化日期  
Date.prototype.format = function (format) //author: meizz   
{
    var o = {
        "M+": this.getMonth() + 1, //month   
        "d+": this.getDate(), //day   
        "h+": this.getHours(), //hour   
        "m+": this.getMinutes(), //minute   
        "s+": this.getSeconds(), //second   
        "q+": Math.floor((this.getMonth() + 3) / 3), //quarter   
        "S": this.getMilliseconds() //millisecond   
    };
    if (/(y+)/.test(format))
        format = format.replace(RegExp.$1,
                (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(format))
            format = format.replace(RegExp.$1,
                    RegExp.$1.length == 1 ? o[k] :
                    ("00" + o[k]).substr(("" + o[k]).length));
    return format;
};
function formatDate(value) {
    if (value == null || value == '') {
        return '';
    }
    var dt = parseToDate(value);//关键代码，将那个长字符串的日期值转换成正常的JS日期格式  
    return dt.format("yyyy-MM-dd"); //这里用到一个javascript的Date类型的拓展方法，这个是自己添加的拓展方法，在后面的步骤3定义  
}
/*带时间*/
function formatDateTime(value) {
    if (value == null || value == '') {
        return '';
    }
    var dt = parseToDate(value);
    return dt.format("yyyy-MM-dd hh:mm:ss");
}
