# 禁止帐户重复登录

## 判断重复登录流程

假设现在数据库中没有任何已记录的 session 信息。

用户 A 登录，数据库中没有当前的 session 信息，则记录 session A 的信息（主要是 `auth`）。

然后用户 B 登录同一个账号（即 `uid` 相同），数据库中没有当前的 session 信息，则记录 session B 的信息。

如果用户 A 再次访问，数据库中有当前的 session 信息，检查是否是同一个账号（`uid` 相同）最新的 session，因为最新的是 session B，所以 A 提示异地登录，强制退出登录。

在强制退出登录之前可以对比一下 `IP` 等信息，如果 IP 段相同，则不一定是异地登录，不需要退出了。

代码中的注释比较详细，具体可以参考注释。

## DiscuzX 登录原理

DiscuzX 中记录登录信息的两个关键 cookie 是 `saltkey` 和 `auth`，只要这两个值不被改变，那么这个会话就被视为已登录，具体请参考 `upload/source/function/function_core.php:153` 中 `authcode` 函数的加解密算法，`upload/source/function/function_member.php:81` 和 `upload/source/class/discuz/discuz_application.php:454` 处的调用。

解码的大致流程：由全局的 `authkey` 和 `saltkey` 生成一个会话的 `authkey`。使用这个 `authkey` 解码 `auth` 得到 `password` 和 `uid`，判断 `uid` 的密码是否等于 `password`。

由此可见只要这两个 cookie 相同，那么就会被视为已登录。可以尝试点击退出登录，然后在把 cookie 复原，这个授权是永久的，除非改变全局 `authkey`。

每次登录的随机产生的 `saltkey` 不同，所以 `auth` 是不一样的，如果同一个用户对应了两个 `auth`，那么很明显，他重复登录了。

如果首次打开网站，cookie 中还没有 `saltkey` 那么会随机生成一个与 `microtime()` 有关的 8 字节的字符串，作为 `saltkey`。

由于这种“永久性登录授权”，导致我们没法彻底登出，防君子不防小人，具体我们就不再讨论。

注意：这里所说的 `password` 是 `pre_common_member` 表中的 `password` 字段，它与用户的密码毫无关联，只是一个 `token` 一样的东西，通过 `md5(random(10))` 生成（具体参考 `upload/source/class/class_member.php:728`），真正的密码储存在 `pre_ucenter_members` 中，这个 `password` 会在用户重置密码时重新生成，所以如果重新这个字段可以达到取消“永久性登录授权”的目的。

## LICENSE

    Multi login detect Discuz! X plugin
    Copyright (C) 2018  Ganlv

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
