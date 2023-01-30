import re
import logging
import argparse
import json
import os
import sys
from enum import Enum
from database import BotDB
from twitchio.ext import commands

parser = argparse.ArgumentParser(
    prog = 'ChillBot',
    description = 'A Twitch.tv chat bot',
    epilog = '')


args = parser.parse_args()

#Setup Logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s  %(message)s',
)

#Initial Extensions
initial_extensions = (
    'cogs.admin',
    'cogs.basic',
    'cogs.mod',
)

#Levels Indicating Permission Level for Commands
class Level(Enum):
    ANY = 0
    VIP = 1
    SUB = 2
    SUBT2 = 3
    SUBT3 = 4
    MOD = 5
    BROADCASTER = 6
    TESTER = 7
    
class ChillBot(commands.Bot):
    
    def __init__(self, config):
        super().__init__(
            token=config["access_token"], 
            prefix=config["command_prefix"], 
            initial_channels=config["channels"]
        )
        self.log = logging
        self.debug = config["debug"]
        self.db = BotDB(config["database"])
        self.valid_ballots = []
        self.voters = []
        self.active_vote_id = -1
        self.prefix = config["command_prefix"]
        self.command_list = [
            {
                "name": "votestart",
                "level": [
                    Level.MOD,
                    Level.BROADCASTER
                ]
            },
            {
                "name": "votestop",
                "level": [
                    Level.MOD,
                    Level.BROADCASTER
                ]
            },
            {
                "name": "votesongs",
                "level": [
                    Level.MOD,
                    Level.BROADCASTER
                ]
            },
            {
                "name": "test",
                "level": [
                    Level.ANY
                ]
            }
        ]

    """
    Event when the bot is connected and listening to messages
    """
    async def event_ready(self):
        self.log.info(f'Ready: {self.nick}')
    
    """
    Event when the bot encounters an error
    Log the error
    """
    async def event_command_error(self, ctx, error):
        self.log.error(f'Error running command: {error} for {ctx.message.author.name}')
        
    async def event_message(self, message):
        if message.echo:
            #Bot Message
            self.log.info(f'#{message.channel.name} - "[Bot]{self.nick} - {message.content}')
        else:
            #User Message
            user_prefix = self.get_author_prefix(message)
            self.log.info(f'#{message.channel.name} - {user_prefix}{message.author.name} - {message.content}')

            #If vote is active, send votes to the db
            if self.active_vote_id is not None and self.active_vote_id > 0:
                vote = self.get_vote_number(message.content)
                if vote in self.valid_ballots and message.author.display_name not in self.voters:
                    print(f"Casting vote for {vote}")
                    result = self.db.cast_vote(message.author.display_name, vote, self.active_vote_id)
                    if result is not None:
                        self.voters.append(message.author.display_name)
                        #await message.channel.send(f"@{message.author.display_name} vote has been cast for song {vote}.")
            #Filter out commands intended for other bots
            if message.content.startswith(self.prefix) and self.is_known_command(message.content):
                await self.handle_commands(message)

    """
    Start the current vote
    """
    @commands.command(name='votestart')
    async def votestart(self, ctx):
        #Make sure author has permission to use this command
        if(not self.check_priv(ctx)): return

        #If already a vote in progress, ignore
        if self.active_vote_id is not None or self.active_vote_id >= 0:
            await ctx.send(f"Poll is already in progress. User !votesongs to list the candidates.") 
            return

        #get the active vote id from the db
        self.active_vote_id = self.db.current_vote()

        if self.active_vote_id is not None:
            candidate_str = ""
            candidates = self.db.get_candidates(self.active_vote_id)
            print(candidates)
            for candidate in candidates:
                self.valid_ballots.append(candidate[0])
                candidate_str += f"{candidate[0]} for {candidate[1]}. "
            await ctx.send(f"Voting has begun! Enter {candidate_str}")
        else:
            await ctx.send(f"No poll has been created yet.")
    
    """
    Stop the current in-progress vote
    """
    @commands.command(name='votestop')
    async def votestop(self, ctx):
        #Make sure author has permission to use this command
        if(not self.check_priv(ctx)): return
        self.active_vote_id = -1
        self.voters = []
        self.valid_ballots = []
        await ctx.send(f"Voting has concluded! The results will be announced shortly.")

    """
    Remind users who they can vote for
    """
    @commands.command(name='votesongs')
    async def votesongs(self, ctx):
        #Make sure author has permission to use this command
        if(not self.check_priv(ctx)): return

        #If already a vote in progress, ignore
        if self.active_vote_id < 0: return

        #get the active vote id from the db
        self.active_vote_id = self.db.current_vote()
        if self.active_vote_id is not None:
            candidate_str = ""
            candidates = self.db.get_candidates(self.active_vote_id)
            #print(candidates)
            for candidate in candidates:
                self.valid_ballots.append(candidate[0])
                candidate_str += f"{candidate[0]} for {candidate[1]}. "
            await ctx.send(f"Get your vote in! Enter {candidate_str}")
            
    @commands.command(name='test')
    async def test(self, ctx):
        if(not self.check_priv(ctx)): return
        n = self.get_vote_number(ctx.message.content)
        await ctx.send(f"Testing! Vote: [{n}]")
    
    """
    Extract the ballot number from a message string or return -1
    """
    def get_vote_number(self, msgstr):
        #Split the string into words and get the first one
        vote = -1
        w = msgstr.split(" ")
        if len(w) > 0 and re.match("[0-9]*$", w[0]) is not None:
            try:
                vote = int(w[0])
            except ValueError:
                return -1
            return vote
        return -1
    
    """
    Check to see if command is in list of valid commands, and ignore the rest
        i.e. ignore commands not intended for this bot
    """
    def is_known_command(self, message_text):
        for cmd in self.command_list:
            if message_text.startswith(self.prefix+cmd["name"]):
                return True
        return False
  
    """
    Check to make sure user has the priv to make a command, returns true/false
    """
    def check_priv(self, ctx):
        levels = self.get_user_level(ctx.author)
        for cmd in self.command_list:
            if(cmd["name"] == ctx.command.name):
                for level in levels:
                    if(level in cmd["level"]):
                        print("Level Matched for User/Command: " + str(level))
                        return True
        return False
    
    """
    Gets the prefix for the log message for a user (uses user levels)
    """
    def get_author_prefix(self, message):
        user_prefix = ''
        levels = self.get_user_level(message.author)
        if Level.SUB in levels:
            user_prefix = '[SubT1]'
        if Level.SUBT2 in levels:
            user_prefix = '[SubT2]'
        if Level.SUBT3 in levels:
            user_prefix = '[SubT3]'
        if Level.MOD in levels:
            user_prefix = '[Mod]'
        if Level.BROADCASTER in levels:
            user_prefix = '[Streamer]'
        if message.author.name.lower() == self.nick.lower():
            user_prefix = '[Bot] '
        return user_prefix
        
    """
    Get the user levels that belong to a user (uses tags/badges)
    """
    def get_user_level(self, user):
        level = [Level.ANY]
        if user is None:
            return level
        if(user.is_mod):
            level.append(Level.MOD)
        if(user.is_subscriber):
            level.append(Level.SUB)
        if(user.badges == None):
            return level
        if(user.badges.get('broadcaster', '0') == '1'):
            level.append(Level.BROADCASTER)
        if(user.badges.get('vip', '0') == '1'):
            level.append(Level.VIP)
        tierStr = user.badges.get('subscriber', "0")
        if(tierStr[0] == "2"):
            level.append(Level.SUBT2)
        if(tierStr[0] == "3"):
            level.append(Level.SUBT3)
        return level

SCRIPT_DIR = os.path.dirname(__file__)
CONFIG_PATH = os.path.join(SCRIPT_DIR, "..", "config", "config.json")

# Make sure config file exists
if not os.path.exists(CONFIG_PATH):
    print(f"Missing config file: {CONFIG_PATH}")
    print("Quitting")
    sys.exit(1)

try:
    with open(CONFIG_PATH) as config_file:
        config = json.load(config_file)
except Exception as e:
    print("Error reading config file")
    print(repr(e))

if not config:
    print("Config file not loaded")
    print("Quitting")
    sys.exit(1)

bot = ChillBot(config)

try:
    bot.run()
except KeyboardInterrupt:
    print("Keyboard Interrupt detected. Quitting.")