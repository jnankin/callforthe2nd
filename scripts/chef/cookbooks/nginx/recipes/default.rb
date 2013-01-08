#
# Cookbook Name:: nginx
# Recipe:: default
#
# Copyright 2012, Phaxio
#
# All rights reserved - Do Not Redistribute
#

package 'nginx' do
  action :install
end

cookbook_file "/etc/nginx/sites-enabled/default.conf" do
  source "default.conf"
  owner "root"
  group "root"
  mode "0644"
  notifies :reload, "service[nginx]"
end

cookbook_file "/etc/nginx/nginx.conf" do
  source "nginx.conf"
  owner "root"
  group "root"
  mode "0644"
  notifies :reload, "service[nginx]"
end

file "/etc/nginx/sites-enabled/default" do
  action :delete
end

service 'nginx' do
  supports :restart => true, :reload => true
  action [ :enable, :restart, :reload ]
end

