import mysql.connector

class BotDB:
    def __init__(self, config) -> None:
        self.config = config
        self.connect()

    def initialize_db(self):
        pass
    
    def connect(self):
        self.db = mysql.connector.connect(
            host=self.config["host"],
            user=self.config["user_name"],
            password=self.config["password"],
            database=self.config["db"]
        )
        if self.is_connected():
            connected = "Connected"
        else:
            connected = "Disconnected"
        print(f"MySQL Connection: {connected}")

    def is_connected(self):
        return self.db.is_connected()

    """
    Reconnect if the db connection timed out
    """
    def check_connection(self, attempts=3):
        while not self.is_connected() and attempts > 0:
            print("DB Connection Timed Out. Reconnecting...")
            self.connect()
            attempts -= 1

    """
    Log a chat message
    """
    def log_chat(self, username, badges, msg):
        self.check_connection()
        sql = "INSERT INTO chat(username, badges, msg) VALUES (%s, %s, %s);"
        cursor = self.db.cursor()
        cursor.execute(sql, (username, badges, msg))
        self.db.commit()
        row_id = cursor.lastrowid
        cursor.close()
        return row_id

    """
    Get the current vote with status "open"
    """
    def current_vote(self):
        self.check_connection()
        sql = "SELECT MAX(id) as current_vote FROM vote WHERE status = 'open'"
        cursor = self.db.cursor()
        cursor.execute(sql)
        result = cursor.fetchone()
        cursor.close()
        if result:
            return result[0]
        return None

    def get_candidates(self, vote_id=None):
        self.check_connection()
        if vote_id is None:
            vote_id = self.current_vote()
            if vote_id is None:
                #No Vote in Progress
                return None
        sql = "SELECT ballot_num, name FROM candidate WHERE vote_id = %s"
        cursor = self.db.cursor()
        cursor.execute(sql, (vote_id,))
        result = cursor.fetchall()
        cursor.close()
        if result:
            return result
        return None

    def cast_vote(self, username, ballot_num, vote_id=None):
        self.check_connection()
        if vote_id is None:
            vote_id = self.current_vote()
            if vote_id is None:
                #No Vote in Progress
                return None
        sql = "INSERT INTO ballot(vote_id, candidate_id, user) VALUES (%s, (SELECT id FROM candidate WHERE vote_id = %s AND ballot_num = %s), %s)"
        cursor = self.db.cursor()
        cursor.execute(sql, (vote_id, vote_id, ballot_num, username))
        self.db.commit()
        row_id = cursor.lastrowid
        cursor.close()
        return row_id

    def vote_summary(self):
        self.check_connection()
        pass