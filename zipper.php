#! /usr/bin/php

<?php

    class php_zipper{
        
        private $za = null;

        public function __construct($args){
            $this->za = new ZipArchive();
            $this->parse_args($args);
        }

        public function parse_args($args){

            if(!in_array('-i', $args) and !in_array('-l', $args)){
                echo "Must provide file(-i) or file(-l) list to assign the file(s) you want to archive.\n";
                exit(1);
            }

            if(in_array('-i', $args)){
                if(in_array('-l', $args)){
                    echo "You can't use -i and -l arguments at the same time.\n";
                    exit(1);
                }
                $this->file = realpath($args[array_search('-i', $args) + 1]);
                if(!$this->file){
                    echo "File '".$args[array_search('-i', $args) + 1]."' doesn't exist.\n";
                    exit(1);
                }
            }

            if(in_array('-l', $args)){
                if(in_array('-i', $args)){
                    echo "You can't use -i and -l arguments at the same time.\n";
                    exit(1);
                }
                $this->file_list = realpath($args[array_search('-l', $args) + 1]);
                if(!$this->file_list){
                    echo "File list '".$args[array_search('-i', $args) + 1]."' doesn't exist.\n";
                    exit(1);
                }
            }

            if(in_array('-o', $args)){
                $this->zip = $args[array_search('-o', $args) + 1];
            }else{
                $zip = isset($this->file) ? $this->file : $this->file_list;
                $path_parts = pathinfo($zip);
                $this->zip = $path_parts['dirname']."/".$path_parts['filename'].".zip";
            }

            if(in_array('-a', $args)){
                $this->append = true;
            }else{
                $this->append = false;
            }

            if(in_array('-L', $args)){
                $this->limit = $args[array_search('-L', $args) + 1] * 1000000;
            }else{
                $this->limit = 1000 * 1000000;
            }
        }

        public function prepare_files(){
            if(isset($this->file)){
                if(file_exists($this->file) and is_file($this->file)){
                    $this->files = array($this->file);
                }else{
                    echo "File $this->file doesn't exist.\n";
                    exit(1);
                }
            }else{
                if(file_exists($this->file_list) and is_file($this->file_list)){
                    $files = file($this->file_list);
                    foreach($files as $file){
                        $this->files[] = rtrim($file); 
                    }
                    
                }else{
                    echo "File list $this->file_list doesn't exist.\n";
                    exit(1);
                }
            }           
        }

        public function create_zip(){

            $this->prepare_files();

            //print_r(get_object_vars($this));
            
            if(file_exists($this->zip) and !$this->append){
                echo "$this->zip is already existed. If you like to add new file into it, please use -a argument.\n";
                exit(1);
            }

            foreach($this->files as $file){

                if(!$this->za->open($this->zip, ZIPARCHIVE::CREATE)){
                    echo "Can't create/open $this->zip.\n";
                    exit(1);
                }

                $this->za->addFile($file, basename($file));

                $this->za->close();

                clearstatcache();

                if(filesize($this->zip) >= $this->limit){
                    echo "$this->zip has already reached max size limit($this->limit bytes). Skip remainning unziped files and finish archive job.\n";
                    exit(10);
                }
            }


            if(file_exists($this->zip)){
                echo "$this->zip is ready, size: ".filesize($this->zip)." byte.\n";
                exit();
            }else{
                echo "Zip job fail.\n";
                exit(1);
            }
        }

    }

    $z = new php_zipper($argv);

    $z->create_zip();
