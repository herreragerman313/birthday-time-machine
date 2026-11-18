"""
Builds sql/seed_data.sql for Birthday Time Machine.

Prices are anchor values (approximate US averages from public sources such as
the BLS CPI, EIA gas data, the National Association of Theatre Owners and The
Economist's Big Mac Index). Years between anchors are filled in with straight
line interpolation, so treat in between years as estimates.

Run:  python3 tools/build_seed.py
"""
import os

FIRST, LAST = 1955, 2026

# ---------- CPI-U annual averages (1982-84 = 100) ----------
cpi = {
    1955: 26.8, 1956: 27.2, 1957: 28.1, 1958: 28.9, 1959: 29.1,
    1960: 29.6, 1961: 29.9, 1962: 30.2, 1963: 30.6, 1964: 31.0, 1965: 31.5,
    1966: 32.4, 1967: 33.4, 1968: 34.8, 1969: 36.7, 1970: 38.8, 1971: 40.5,
    1972: 41.8, 1973: 44.4, 1974: 49.3, 1975: 53.8, 1976: 56.9, 1977: 60.6,
    1978: 65.2, 1979: 72.6, 1980: 82.4, 1981: 90.9, 1982: 96.5, 1983: 99.6,
    1984: 103.9, 1985: 107.6, 1986: 109.6, 1987: 113.6, 1988: 118.3,
    1989: 124.0, 1990: 130.7, 1991: 136.2, 1992: 140.3, 1993: 144.5,
    1994: 148.2, 1995: 152.4, 1996: 156.9, 1997: 160.5, 1998: 163.0,
    1999: 166.6, 2000: 172.2, 2001: 177.1, 2002: 179.9, 2003: 184.0,
    2004: 188.9, 2005: 195.3, 2006: 201.6, 2007: 207.3, 2008: 215.3,
    2009: 214.5, 2010: 218.1, 2011: 224.9, 2012: 229.6, 2013: 233.0,
    2014: 236.7, 2015: 237.0, 2016: 240.0, 2017: 245.1, 2018: 251.1,
    2019: 255.7, 2020: 258.8, 2021: 271.0, 2022: 292.7, 2023: 304.7,
    2024: 313.7, 2025: 322.0, 2026: 330.0,
}

# ---------- Gas, US average regular, $/gallon ----------
gas = {
    1955: 0.29, 1960: 0.31, 1965: 0.31, 1970: 0.36, 1973: 0.39, 1974: 0.53,
    1975: 0.57, 1976: 0.59, 1977: 0.62, 1978: 0.63, 1979: 0.86, 1980: 1.19,
    1981: 1.31, 1982: 1.22, 1983: 1.16, 1984: 1.13, 1985: 1.12, 1986: 0.86,
    1987: 0.90, 1988: 0.90, 1989: 1.00, 1990: 1.15, 1991: 1.14, 1992: 1.13,
    1993: 1.11, 1994: 1.11, 1995: 1.15, 1996: 1.23, 1997: 1.23, 1998: 1.06,
    1999: 1.17, 2000: 1.51, 2001: 1.46, 2002: 1.36, 2003: 1.59, 2004: 1.88,
    2005: 2.30, 2006: 2.59, 2007: 2.80, 2008: 3.27, 2009: 2.35, 2010: 2.79,
    2011: 3.53, 2012: 3.64, 2013: 3.53, 2014: 3.37, 2015: 2.45, 2016: 2.14,
    2017: 2.42, 2018: 2.72, 2019: 2.60, 2020: 2.17, 2021: 3.01, 2022: 3.95,
    2023: 3.52, 2024: 3.30, 2025: 3.15, 2026: 3.10,
}

# ---------- Average movie ticket ----------
ticket = {
    1955: 0.50, 1960: 0.69, 1965: 1.01, 1970: 1.55, 1975: 2.05, 1980: 2.69,
    1985: 3.55, 1990: 4.23, 1995: 4.35, 2000: 5.39, 2005: 6.41, 2010: 7.89,
    2015: 8.43, 2019: 9.16, 2022: 10.53, 2023: 10.78, 2024: 11.31,
    2025: 11.50, 2026: 11.70,
}

# ---------- Big Mac (introduced nationally 1968) ----------
big_mac = {
    1968: 0.49, 1975: 0.75, 1980: 1.10, 1986: 1.60, 1990: 2.20, 2000: 2.51,
    2005: 3.06, 2010: 3.73, 2015: 4.79, 2020: 5.67, 2022: 5.15, 2023: 5.58,
    2024: 5.69, 2025: 5.79, 2026: 5.90,
}

# ---------- Average new car ----------
new_car = {
    1955: 1900, 1960: 2600, 1965: 2650, 1970: 3900, 1975: 4950, 1980: 7200,
    1985: 11450, 1990: 16000, 1995: 20450, 2000: 24750, 2005: 28400,
    2010: 29217, 2015: 33560, 2020: 38000, 2022: 46500, 2023: 48000,
    2024: 48400, 2025: 49000, 2026: 49500,
}

# ---------- Billboard year end #1 single ----------
songs = {
    1955: ("Cherry Pink and Apple Blossom White", "Perez Prado"),
    1956: ("Heartbreak Hotel", "Elvis Presley"),
    1957: ("All Shook Up", "Elvis Presley"),
    1958: ("Volare (Nel Blu Dipinto di Blu)", "Domenico Modugno"),
    1959: ("The Battle of New Orleans", "Johnny Horton"),
    1960: ("Theme from A Summer Place", "Percy Faith"),
    1961: ("Tossin' and Turnin'", "Bobby Lewis"),
    1962: ("Stranger on the Shore", "Acker Bilk"),
    1963: ("Sugar Shack", "Jimmy Gilmer and the Fireballs"),
    1964: ("I Want to Hold Your Hand", "The Beatles"),
    1965: ("Wooly Bully", "Sam the Sham and the Pharaohs"),
    1966: ("The Ballad of the Green Berets", "SSgt. Barry Sadler"),
    1967: ("To Sir with Love", "Lulu"),
    1968: ("Hey Jude", "The Beatles"),
    1969: ("Sugar, Sugar", "The Archies"),
    1970: ("Bridge over Troubled Water", "Simon & Garfunkel"),
    1971: ("Joy to the World", "Three Dog Night"),
    1972: ("The First Time Ever I Saw Your Face", "Roberta Flack"),
    1973: ("Tie a Yellow Ribbon Round the Ole Oak Tree", "Dawn"),
    1974: ("The Way We Were", "Barbra Streisand"),
    1975: ("Love Will Keep Us Together", "Captain & Tennille"),
    1976: ("Silly Love Songs", "Wings"),
    1977: ("Tonight's the Night (Gonna Be Alright)", "Rod Stewart"),
    1978: ("Shadow Dancing", "Andy Gibb"),
    1979: ("My Sharona", "The Knack"),
    1980: ("Call Me", "Blondie"),
    1981: ("Bette Davis Eyes", "Kim Carnes"),
    1982: ("Physical", "Olivia Newton-John"),
    1983: ("Every Breath You Take", "The Police"),
    1984: ("When Doves Cry", "Prince"),
    1985: ("Careless Whisper", "Wham! featuring George Michael"),
    1986: ("That's What Friends Are For", "Dionne & Friends"),
    1987: ("Walk Like an Egyptian", "The Bangles"),
    1988: ("Faith", "George Michael"),
    1989: ("Look Away", "Chicago"),
    1990: ("Hold On", "Wilson Phillips"),
    1991: ("(Everything I Do) I Do It for You", "Bryan Adams"),
    1992: ("End of the Road", "Boyz II Men"),
    1993: ("I Will Always Love You", "Whitney Houston"),
    1994: ("The Sign", "Ace of Base"),
    1995: ("Gangsta's Paradise", "Coolio featuring L.V."),
    1996: ("Macarena (Bayside Boys Mix)", "Los del Rio"),
    1997: ("Candle in the Wind 1997", "Elton John"),
    1998: ("Too Close", "Next"),
    1999: ("Believe", "Cher"),
    2000: ("Breathe", "Faith Hill"),
    2001: ("Hanging by a Moment", "Lifehouse"),
    2002: ("How You Remind Me", "Nickelback"),
    2003: ("In da Club", "50 Cent"),
    2004: ("Yeah!", "Usher featuring Lil Jon & Ludacris"),
    2005: ("We Belong Together", "Mariah Carey"),
    2006: ("Bad Day", "Daniel Powter"),
    2007: ("Irreplaceable", "Beyonce"),
    2008: ("Low", "Flo Rida featuring T-Pain"),
    2009: ("Boom Boom Pow", "The Black Eyed Peas"),
    2010: ("Tik Tok", "Kesha"),
    2011: ("Rolling in the Deep", "Adele"),
    2012: ("Somebody That I Used to Know", "Gotye featuring Kimbra"),
    2013: ("Thrift Shop", "Macklemore & Ryan Lewis featuring Wanz"),
    2014: ("Happy", "Pharrell Williams"),
    2015: ("Uptown Funk", "Mark Ronson featuring Bruno Mars"),
    2016: ("Love Yourself", "Justin Bieber"),
    2017: ("Shape of You", "Ed Sheeran"),
    2018: ("God's Plan", "Drake"),
    2019: ("Old Town Road", "Lil Nas X featuring Billy Ray Cyrus"),
    2020: ("Blinding Lights", "The Weeknd"),
    2021: ("Levitating", "Dua Lipa"),
    2022: ("Heat Waves", "Glass Animals"),
    2023: ("Last Night", "Morgan Wallen"),
    2024: ("Lose Control", "Teddy Swims"),
}

# ---------- Top domestic box office movie (by release year) ----------
movies = {
    1970: "Love Story", 1971: "Fiddler on the Roof", 1972: "The Godfather",
    1973: "The Exorcist", 1974: "Blazing Saddles", 1975: "Jaws",
    1976: "Rocky", 1977: "Star Wars", 1978: "Grease",
    1979: "Kramer vs. Kramer", 1980: "The Empire Strikes Back",
    1981: "Raiders of the Lost Ark", 1982: "E.T. the Extra-Terrestrial",
    1983: "Return of the Jedi", 1984: "Beverly Hills Cop",
    1985: "Back to the Future", 1986: "Top Gun", 1987: "Three Men and a Baby",
    1988: "Rain Man", 1989: "Batman", 1990: "Home Alone",
    1991: "Terminator 2: Judgment Day", 1992: "Aladdin",
    1993: "Jurassic Park", 1994: "Forrest Gump", 1995: "Toy Story",
    1996: "Independence Day", 1997: "Titanic", 1998: "Saving Private Ryan",
    1999: "Star Wars: Episode I - The Phantom Menace",
    2000: "How the Grinch Stole Christmas",
    2001: "Harry Potter and the Sorcerer's Stone", 2002: "Spider-Man",
    2003: "The Lord of the Rings: The Return of the King", 2004: "Shrek 2",
    2005: "Star Wars: Episode III - Revenge of the Sith",
    2006: "Pirates of the Caribbean: Dead Man's Chest",
    2007: "Spider-Man 3", 2008: "The Dark Knight", 2009: "Avatar",
    2010: "Toy Story 3",
    2011: "Harry Potter and the Deathly Hallows: Part 2",
    2012: "The Avengers", 2013: "The Hunger Games: Catching Fire",
    2014: "American Sniper", 2015: "Star Wars: The Force Awakens",
    2016: "Rogue One: A Star Wars Story", 2017: "Star Wars: The Last Jedi",
    2018: "Black Panther", 2019: "Avengers: Endgame",
    2020: "Bad Boys for Life", 2021: "Spider-Man: No Way Home",
    2022: "Top Gun: Maverick", 2023: "Barbie", 2024: "Inside Out 2",
}

# ---------- Prices that change on exact dates ----------
stamps = [
    ("1958-08-01", 0.04), ("1963-01-07", 0.05), ("1968-01-07", 0.06),
    ("1971-05-16", 0.08), ("1974-03-02", 0.10), ("1975-12-31", 0.13),
    ("1978-05-29", 0.15), ("1981-03-22", 0.18), ("1981-11-01", 0.20),
    ("1985-02-17", 0.22), ("1988-04-03", 0.25), ("1991-02-03", 0.29),
    ("1995-01-01", 0.32), ("1999-01-10", 0.33), ("2001-01-07", 0.34),
    ("2002-06-30", 0.37), ("2006-01-08", 0.39), ("2007-05-14", 0.41),
    ("2008-05-12", 0.42), ("2009-05-11", 0.44), ("2012-01-22", 0.45),
    ("2013-01-27", 0.46), ("2014-01-26", 0.49), ("2016-04-10", 0.47),
    ("2017-01-22", 0.49), ("2018-01-21", 0.50), ("2019-01-27", 0.55),
    ("2021-08-29", 0.58), ("2022-07-10", 0.60), ("2023-01-22", 0.63),
    ("2023-07-09", 0.66), ("2024-01-21", 0.68), ("2024-07-14", 0.73),
    ("2025-07-13", 0.78),
]
stamps.insert(0, ("1932-07-06", 0.03))

min_wage = [
    ("1950-01-25", 0.75), ("1956-03-01", 1.00), ("1961-09-03", 1.15),
    ("1963-09-03", 1.25), ("1967-02-01", 1.40), ("1968-02-01", 1.60),
    ("1974-05-01", 2.00), ("1975-01-01", 2.10), ("1976-01-01", 2.30),
    ("1978-01-01", 2.65), ("1979-01-01", 2.90), ("1980-01-01", 3.10),
    ("1981-01-01", 3.35), ("1990-04-01", 3.80), ("1991-04-01", 4.25),
    ("1996-10-01", 4.75), ("1997-09-01", 5.15), ("2007-07-24", 5.85),
    ("2008-07-24", 6.55), ("2009-07-24", 7.25),
]

# ---------- Inventions: (name, emoji, start, end, note) ----------
inventions = [
    ("Color TV in most homes", "📺", "1972-01-01", None, "Color sets passed black and white in US homes around 1972"),
    ("Pong", "🏓", "1972-11-29", None, "The first hit arcade video game"),
    ("Apple (the company)", "🍎", "1976-04-01", None, None),
    ("VHS tapes", "📼", "1977-01-01", "2016-07-31", "The last VCR was made in 2016"),
    ("Sony Walkman", "🎧", "1979-07-01", None, "Music you could carry"),
    ("Post-it Notes", "🟨", "1980-04-06", None, None),
    ("MTV", "📡", "1981-08-01", None, None),
    ("The Space Shuttle", "🚀", "1981-04-12", "2011-07-21", "Flew 135 missions"),
    ("Cell phones you could buy", "📱", "1983-10-13", None, "The first one cost $3,995"),
    ("Blockbuster Video", "🎬", "1985-10-19", "2014-01-12", "Be kind, please rewind"),
    ("Nintendo (NES) in the US", "🎮", "1985-10-18", None, None),
    ("The World Wide Web", "🌐", "1991-08-06", None, None),
    ("Text messages", "💬", "1992-12-03", None, "The first text said Merry Christmas"),
    ("Amazon", "📦", "1995-07-16", None, "It started as an online bookstore"),
    ("eBay", "🛒", "1995-09-03", None, None),
    ("PlayStation in the US", "🕹️", "1995-09-09", None, None),
    ("Internet Explorer", "🧭", "1995-08-16", "2022-06-15", None),
    ("Adobe Flash games", "⚡", "1996-12-01", "2020-12-31", None),
    ("AOL Instant Messenger", "🟡", "1997-05-01", "2017-12-15", "Away messages were an art"),
    ("DVDs", "💿", "1997-03-01", None, None),
    ("Google", "🔎", "1998-09-04", None, None),
    ("Netflix DVDs by mail", "✉️", "1998-04-14", "2023-09-29", "The red envelopes stopped in 2023"),
    ("Wi-Fi in homes", "📶", "1999-07-21", None, None),
    ("BlackBerry phones", "⌨️", "1999-01-19", "2022-01-04", None),
    ("Wikipedia", "📚", "2001-01-15", None, None),
    ("The iPod", "🎵", "2001-10-23", "2022-05-10", "1,000 songs in your pocket"),
    ("Xbox", "🟩", "2001-11-15", None, None),
    ("Skype", "☎️", "2003-08-29", "2025-05-05", None),
    ("MySpace", "👤", "2003-08-01", None, "Tom was everyone's first friend"),
    ("Facebook", "👍", "2004-02-04", None, None),
    ("YouTube", "▶️", "2005-02-14", None, "The first video was at the zoo"),
    ("Club Penguin", "🐧", "2005-10-24", "2017-03-29", None),
    ("Twitter / X", "🐦", "2006-03-21", None, None),
    ("The iPhone", "📲", "2007-06-29", None, None),
    ("Netflix streaming", "🍿", "2007-01-16", None, None),
    ("Uber", "🚗", "2009-05-31", None, None),
    ("Instagram", "📸", "2010-10-06", None, None),
    ("Google+", "➕", "2011-06-28", "2019-04-02", None),
    ("Snapchat", "👻", "2011-07-08", None, None),
    ("Spotify in the US", "🟢", "2011-07-14", None, None),
    ("Tinder", "🔥", "2012-09-12", None, None),
    ("Vine", "🌱", "2013-01-24", "2017-01-17", "Six seconds of fame"),
    ("TikTok in the US", "🎶", "2018-08-02", None, None),
    ("ChatGPT", "🤖", "2022-11-30", None, None),
]

# ---------- Tech by era: (category, start, end, label, detail) ----------
tech = [
    ("phone", 1900, 1983, "A landline phone on the wall", "Rotary dial or push buttons, with a cord"),
    ("phone", 1984, 1989, "The brick phone", "Motorola DynaTAC: about 2 pounds and $3,995"),
    ("phone", 1990, 1996, "A flip phone or a pager", "Beepers and Motorola flip phones"),
    ("phone", 1997, 2002, "A Nokia candybar phone", "Snake was the main game"),
    ("phone", 2003, 2006, "The Motorola RAZR", "The thin flip phone everyone wanted"),
    ("phone", 2007, 2009, "The first iPhone or a BlackBerry", "Touch screens just showed up"),
    ("phone", 2010, 2016, "iPhone 4 to iPhone 7, Galaxy S", "Apps took over"),
    ("phone", 2017, 2020, "iPhone X to iPhone 12", "Face ID and no home button"),
    ("phone", 2021, 2100, "iPhone 13 and up, foldables", "Phones with 3 or more cameras"),

    ("music", 1900, 1964, "Vinyl records and AM radio", None),
    ("music", 1965, 1979, "Vinyl and 8 track tapes", None),
    ("music", 1980, 1987, "Cassette tapes and the Walkman", "Making mixtapes off the radio"),
    ("music", 1988, 2000, "CDs and the Discman", "Don't move or it skips"),
    ("music", 2001, 2007, "iPods and MP3s", "Burning CDs and LimeWire"),
    ("music", 2008, 2014, "iTunes downloads and Pandora", "99 cents a song"),
    ("music", 2015, 2100, "Spotify and Apple Music streaming", "Every song ever, anytime"),

    ("internet", 1900, 1990, "No internet for regular people", "Encyclopedias and the library"),
    ("internet", 1991, 1995, "Dial up at 14.4k", "Tie up the phone line to go online"),
    ("internet", 1996, 2001, "56k dial up and AOL CDs", "That screeching modem sound"),
    ("internet", 2002, 2008, "DSL or cable, about 1 to 5 Mbps", None),
    ("internet", 2009, 2015, "Broadband around 10 to 50 Mbps and 3G/4G", None),
    ("internet", 2016, 2100, "100+ Mbps home internet and 5G", None),

    ("gaming", 1900, 1976, "Board games and pinball", None),
    ("gaming", 1977, 1984, "Atari 2600", None),
    ("gaming", 1985, 1990, "Nintendo Entertainment System", None),
    ("gaming", 1991, 1994, "Super Nintendo and Sega Genesis", None),
    ("gaming", 1995, 1999, "PlayStation and Nintendo 64", None),
    ("gaming", 2000, 2004, "PlayStation 2", "The best selling console ever"),
    ("gaming", 2005, 2012, "Xbox 360, PS3 and the Wii", None),
    ("gaming", 2013, 2019, "PS4, Xbox One and Nintendo Switch", None),
    ("gaming", 2020, 2100, "PS5, Xbox Series X and Switch", None),

    ("tv", 1900, 1971, "A black and white TV with an antenna", "Get up to change the channel"),
    ("tv", 1972, 1989, "A color tube TV", "Cable TV started spreading"),
    ("tv", 1990, 2004, "A big heavy CRT TV", "It took two people to carry it"),
    ("tv", 2005, 2014, "A flat screen HD TV", None),
    ("tv", 2015, 2100, "A 4K smart TV with streaming apps", None),
]


def interp(table, year):
    if year in table:
        return table[year]
    keys = sorted(table)
    if year < keys[0] or year > keys[-1]:
        return None
    lo = max(k for k in keys if k < year)
    hi = min(k for k in keys if k > year)
    return table[lo] + (table[hi] - table[lo]) * (year - lo) / (hi - lo)


def q(s):
    if s is None:
        return "NULL"
    return "'" + str(s).replace("\\", "\\\\").replace("'", "''") + "'"


def num(v, digits=2):
    return "NULL" if v is None else f"{v:.{digits}f}"


lines = [
    "-- Birthday Time Machine seed data (generated by tools/build_seed.py)",
    "USE birthday_time_machine;",
    "SET NAMES utf8mb4;",
    "DELETE FROM year_facts; DELETE FROM dated_prices; DELETE FROM inventions; DELETE FROM tech_eras;",
    "",
]

rows = []
for y in range(FIRST, LAST + 1):
    song = songs.get(y, (None, None))
    car = interp(new_car, y)
    rows.append(
        f"({y},{num(interp(cpi, y))},{num(interp(gas, y))},{num(interp(ticket, y))},"
        f"{num(interp(big_mac, y))},{'NULL' if car is None else int(round(car, -1))},"
        f"{q(song[0])},{q(song[1])},{q(movies.get(y))})"
    )
lines.append("INSERT INTO year_facts (year,cpi,gas_gallon,movie_ticket,big_mac,new_car,song_title,song_artist,movie_title) VALUES")
lines.append(",\n".join(rows) + ";")
lines.append("")

rows = [f"('stamp','{d}',{p:.2f})" for d, p in stamps] + [f"('min_wage','{d}',{p:.2f})" for d, p in min_wage]
lines.append("INSERT INTO dated_prices (item,start_date,price) VALUES")
lines.append(",\n".join(rows) + ";")
lines.append("")

rows = [f"({q(n)},{q(e)},{q(s)},{q(en)},{q(note)})" for n, e, s, en, note in inventions]
lines.append("INSERT INTO inventions (name,emoji,start_date,end_date,note) VALUES")
lines.append(",\n".join(rows) + ";")
lines.append("")

rows = [f"({q(c)},{a},{b},{q(l)},{q(d)})" for c, a, b, l, d in tech]
lines.append("INSERT INTO tech_eras (category,start_year,end_year,label,detail) VALUES")
lines.append(",\n".join(rows) + ";")

out = os.path.join(os.path.dirname(__file__), "..", "sql", "seed_data.sql")
with open(out, "w", encoding="utf-8") as f:
    f.write("\n".join(lines) + "\n")
print("Wrote", os.path.abspath(out))
