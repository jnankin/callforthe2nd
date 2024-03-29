#
# Author::  Joshua Timberman (<joshua@opscode.com>)
# Author::  Seth Chisamore (<schisamo@opscode.com>)
# Cookbook Name:: php
# Recipe:: module_memcache
#
# Copyright 2009-2011, Opscode, Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

case node['platform']
when "centos", "redhat", "fedora"
  %w{ zlib-devel }.each do |pkg|
    package pkg do
      action :install
    end
  end
  php_pear "memcache" do
    action :install
    #directives(:shm_size => "128M", :enable_cli => 0)
  end
when "debian", "ubuntu"
  package "php5-memcache" do
    action :install
  end
end

template "/etc/php5/conf.d/memcache.ini" do
  source "memcache.ini.erb"
  owner "root"
  group "root"
  mode "0644"
  notifies :restart, "service[php5-fpm]"
end