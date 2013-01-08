#
# Cookbook Name:: php5-fpm
# Recipe:: default
#
# Copyright 2012, Phaxio
#
# All rights reserved - Do Not Redistribute
#

package 'php5-fpm' do
  action :install
end

service 'php5-fpm' do
  supports :restart => true
  action [ :enable, :start ]
end

cookbook_file "/etc/php5/fpm/php.ini" do
  source "php.ini"
  owner "root"
  group "root"
  mode "0644"
  notifies :restart, "service[php5-fpm]"
end

directory '/var/log/php/fpm' do
  mode '0777'
  recursive true
end