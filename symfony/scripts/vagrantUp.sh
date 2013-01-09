#!/bin/bash

sudo apt-get update
sudo apt-get install -y git-core curl
cd ~
git clone https://github.com/ddollar/foreman.git
git clone https://github.com/ddollar/mason.git

cd foreman
gem build *.gemspec
sudo gem install *.gem

cd ../mason
gem build *.gemspec
sudo gem install *.gem

git clone https://github.com/travisj/heroku-buildpack-nginx-php.git
#heroku-buildpack-nginx-php
