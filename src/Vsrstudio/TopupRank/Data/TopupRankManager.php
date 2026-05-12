<?php

namespace VsrStudio\TopupRank\Data;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

use IvanCraft623\RankSystem\RankSystem;
use IvanCraft623\RankSystem\rank\Rank;

use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\PPGroup;

class TopupRankManager {

    private Plugin $plugin;

    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * GIVE RANK
     */
    public function grantRank(string $gamertag, string $rank): bool {

        $server = Server::getInstance();
        $pluginManager = $server->getPluginManager();

        /*
         * =========================
         * RANKSYSTEM PM5
         * =========================
         */
        $rankSystem = $pluginManager->getPlugin("RankSystem");

        if ($rankSystem instanceof RankSystem) {

            $player = $server->getPlayerExact($gamertag);

            /*
             * ONLINE PLAYER
             */
            if ($player instanceof Player) {

                $session = $rankSystem
                    ->getSessionManager()
                    ->get($player);

                $rankObj = $rankSystem
                    ->getRankManager()
                    ->getRank($rank);

                if ($rankObj instanceof Rank) {

                    $success = $session->setRank($rankObj);

                    if ($success) {

                        $this->sendSuccessMessage(
                            $gamertag,
                            $rank
                        );

                        return true;
                    }
                }
            }

            /*
             * OFFLINE PLAYER
             * RankSystem PM5 tetap gunakan command
             */
            $server->dispatchCommand(
                $server->getConsoleSender(),
                "ranks setrank {$gamertag} {$rank}"
            );

            return true;
        }

        /*
         * =========================
         * PUREPERMS PM5
         * =========================
         */
        $purePerms = $pluginManager->getPlugin("PurePerms");

        if ($purePerms instanceof PurePerms) {

            $group = $purePerms->getGroup($rank);

            if ($group instanceof PPGroup) {

                $player = $server->getPlayerExact($gamertag);

                /*
                 * ONLINE PLAYER
                 */
                if ($player instanceof Player) {

                    $purePerms->setGroup($player, $group);

                } else {

                    /*
                     * OFFLINE PLAYER
                     */
                    $server->dispatchCommand(
                        $server->getConsoleSender(),
                        "setgroup {$gamertag} {$rank}"
                    );
                }

                $this->sendSuccessMessage(
                    $gamertag,
                    $rank
                );

                return true;
            }
        }

        return false;
    }

    /**
     * CHECK VALID RANK
     */
    public function isValidRank(string $rank): bool {

        $server = Server::getInstance();
        $pluginManager = $server->getPluginManager();

        /*
         * RANKSYSTEM
         */
        $rankSystem = $pluginManager->getPlugin("RankSystem");

        if ($rankSystem instanceof RankSystem) {

            return $rankSystem
                ->getRankManager()
                ->getRank($rank) instanceof Rank;
        }

        /*
         * PUREPERMS
         */
        $purePerms = $pluginManager->getPlugin("PurePerms");

        if ($purePerms instanceof PurePerms) {

            return $purePerms
                ->getGroup($rank) instanceof PPGroup;
        }

        return false;
    }

    /**
     * SEND SUCCESS MESSAGE
     */
    private function sendSuccessMessage(
        string $gamertag,
        string $rank
    ): void {

        $player = Server::getInstance()
            ->getPlayerExact($gamertag);

        if ($player instanceof Player) {

            $player->sendMessage(
                "§aSelamat! Rank §e{$rank} §aberhasil diberikan."
            );
        }
    }
}
