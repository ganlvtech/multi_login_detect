# 禁止帐户重复登录

## 原理

DiscuzX 中记录登录信息的两个 Cookie 是 `saltkey` 和 `auth` 这两个值，只要这两个值一模一样，那么这个cookie会被授权大约115天左右的时间，这个取决于 `function_core.php` 中 `modauthkey` 加密函数的算法。

每次登录的随机产生的 `saltkey` 不同，所以 `auth` 是不一样的，如果同一个用户对应了两个 `auth`， 那么很明显，他重复登录了。

另外一个判断方法就是，如果 `IP` 不同，则一定重复登录了。

代码中的注释比较详细，具体可以参考注释。

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
