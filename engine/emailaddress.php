<?php

class EmailAddress
{
    /**
     * Simple validation of a email.
     *
     * @param string $address
     * @throws ValidationException on invalid
     * @return bool
     */
    static function validate($address)
    {
        if ($address !== "" && !static::is_valid($address))
            throw new ValidationException(sprintf(__('register:notemail'), $address));

        return $address;
    }
    
    static function is_valid($address)
    {
        return preg_match('/^[A-Z0-9\._\%\+\-]+@[A-Z0-9\.\-]+\.[A-Z0-9]+$/i', $address);
    }
    
    /*
     * Adds a tag to an email address (like user+tag@domain) that we can be reasonably sure
     * was generated by Envaya and not tampered with. If we receive a reply to an email
     * address containing a valid signed tag, we can assume it was sent by the user who we 
     * sent the email to.
     */
    static function add_signed_tag($address, $tag)
    {
        $site_secret = Config::get('site_secret');        
        $timestamp = timestamp();
        $signature = static::generate_tag_signature($tag, $timestamp);
        
        return str_replace('@', "+$tag-$timestamp-$signature@", $address);
    }    
    
    private static function generate_tag_signature($tag, $timestamp)
    {
        $data = ":$timestamp:$tag";
        $site_secret = Config::get('site_secret');
        
        $okey = md5($site_secret);
        $ikey = md5($okey);
                
        // more or less like HMAC-sha1
        return substr(sha1($okey.sha1($ikey.$data, true)), 0, 22);
    }
    
    static function get_signed_tag($address)
    {
        if (preg_match('#\+(\w+)\-(\d+)\-(\w+)@#', $address, $match))
        {
            $tag = $match[1];
            $timestamp = $match[2];
            
            // expire signed tags after 30 days (addresses could end up on spam lists eventually)
            if (abs(timestamp() - $timestamp) > 86400 * 30)
            {
                return null;
            }            
                        
            if ($match[3] === static::generate_tag_signature($tag, $timestamp))
            {
                return $tag;
            }
        }
        return null;
    }
    
    /*
     * Parses an address like 'person name <user@host>' and returns array
     * with keys 'name' and 'address'
     */
    static function parse_address($rfc822_address)
    {
        require_once Config::get('root')."/vendors/rfc822_addresses.php";
    
        $rfc822 = new rfc822_addresses_class();
        $rfc822->ParseAddressList($rfc822_address, $parsed_address);
    
        return @$parsed_address[0];
    }        
}