<?php

# +-------------------------------------------------+
# |             MCPEToDiscord - VER 1.3             |
# |-------------------------------------------------|
# |                                                 |
# | Made by : Jackthehack21 (gangnam253@gmail.com)  |
# |                                                 |
# | Build   : 055#A                                 |
# |                                                 |
# | Details : This plugin is aimed to give players  |
# |           A simple but fun view of what plugins |
# |           Can do to modify your MCPE experience.|
# |                                                 |
# +-------------------------------------------------+

namespace Jack\DiscordMCPE;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\{PlayerJoinEvent,PlayerQuitEvent, PlayerDeathEvent, PlayerChatEvent};;


class Main extends PluginBase implements Listener{
		
	public function onEnable(){
        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
            //Use default, not PM.
        }
        $this->saveResource("config.yml");
        $this->saveResource("help.txt");
        $this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML, []);
        $this->language = strtolower($this->cfg->get("language"));
        $os = array('english', 'spanish', 'german', 'chinese');
        if (in_array($this->language, $os) == false) {
            $this->language = 'english';
        }
	$this->saveResource("lang/".$this->language.".yml");
        $this->responses = new Config($this->getDataFolder()."lang/".$this->language.".yml", Config::YAML, []);
        if($this->cfg->get('debug')){
            $this->getLogger()->info($this->responses->get("enabled_debug"));
        }
        $tmp = $this->cfg->get("discord");
        $this->enabled = false;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($tmp == false){
            $this->getLogger()->info(C::RED.$this->responses->get('disabled_config'));
            return;
        }
        if($tmp == true){
            $url = $this->cfg->get("webhook_url");
            $query = "https://discordapp.com/api/webhooks/";
            if(substr($url, 0, strlen($query)) == $query) {
                $this->enabled = true;
                if($this->cfg->get('other_pluginEnabled?') === true){
                    $this->sendMessage("Enable", $this->cfg->get('other_pluginEnabledFormat'));
                }
                return;
            } else {
                $this->getLogger()->warning($this->responses->get('enabled_incomplete'));
		return;
            }
        } 
        $this->getLogger()->warning($this->responses->get('disabled_config'));
        return;
	}
	
	public function onDisable(){
        if($this->cfg->get('debug')){
            $this->getLogger()->info($this->responses->get("disabled"));
        }
        if($this->cfg->get('other_pluginDisabled?') === true){
            $this->sendMessage("Disabled", $this->cfg->get('other_pluginDisabledFormat'));
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
		if($cmd->getName() == "discord"){
            if(!$this->enabled) {
                $sender->sendMessage(C::RED.$this->responses->get("disabled"));
                return true;
            }
			if(!isset($args[0])) {
				$sender->sendMessage(C::RED.$this->responses->get("args_missing"));
                return true;
			}
            if(!$sender instanceof Player){
                $sender->sendMessage(C::RED.$this->responses->get("ingame"));
                return true;
            }
			else{
                $name = $sender->getName();
                $msg = implode(" ", $args);
                $check = $this->getConfig()->get("discord");
                if($this->enabled == false){ 
                    $sender->sendMessage(C::RED.$this->responses->get("command_disabled"));
                    return true;
                } else {
                    $this->sendMessage($name, "[".$sender->getNameTag()."] : ".implode(" ", $args));
                    $sender->sendMessage(C::AQUA.$this->responses->get("send_success"));
                }
			}
            return true;
		}
	    return false;
	}
    
    /**
     * @param PlayerJoinEvent $event
     */
	public function onJoin(PlayerJoinEvent $event){
        $playername = $event->getPlayer()->getDisplayName();
        if($this->cfg->get("webhook_playerJoin?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerJoinFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($playername, $msg);
    }

    public function onQuit(PlayerQuitEvent $event){
        $playername = $event->getPlayer()->getDisplayName();
        if($this->cfg->get("webhook_playerLeave?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerLeaveFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($playername, $msg);
    }

    public function onDeath(PlayerDeathEvent $event){
        $playername = $event->getPlayer()->getDisplayName();
        if($this->cfg->get("webhook_playerDeath?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerDeathFormat");
        $msg = str_replace("{player}",$playername,$format);
        $this->sendMessage($playername, $msg);
    }

    public function onChat(PlayerChatEvent $event){
	    $playername = $event->getPlayer()->getName();
        $message = $event->getMessage();
        $ar = getdate();
        $time = $ar['hours'].":".$ar['minutes'];
        if($this->cfg->get("webhook_playerChat?") !== true){
            return;
        }
        $format = $this->cfg->get("webhook_playerChatFormat");
        $msg = str_replace("{msg}",$message, str_replace("{time}",$time, str_replace("{player}",$playername,$format)));
        $this->sendMessage($playername, $msg);
    }

    public function backFromAsync($player, $result){
        if($player === "nolog"){
            return;
        }
        elseif ($player === "CONSOLE"){
            $player = new ConsoleCommandSender();
        }
        else{
            $playerinstance = $this->getServer()->getPlayerExact($player);
            if ($playerinstance === null){
                return;
            }
            else{
                $player = $playerinstance;
            }
        }
        if($result["success"]) {
            $player->sendMessage(C::AQUA."[MCPE->Discord] ".C::GREEN.$this->responses->get("send_success"));
        }
        else{
            $this->getLogger()->error(C::RED."Error: ".$result["Error"]);
            $player->sendMessage(C::AQUA."[MCPE->Discord]] ".C::GREEN.$this->responses->get("send_fail"));
        }
    }

    /**
     * @param $message
     */
    public function sendMessage(string $player = "nolog", string $msg){
        if(!$this->enabled){
            return;
        }
        $name = $this->cfg->get("webhook_name");
        $webhook = $this->cfg->get("webhook_url");
        $curlopts = [
	    "content" => $msg,
            "username" => $name
        ];

        $this->getPlugin()->getScheduler()->scheduleAsyncTask(new tasks\SendAsync($player, $webhook, serialize($curlopts)));
        return true;
    }
}
