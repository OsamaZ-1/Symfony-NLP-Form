import spacy
import re
import json
import sys

def extract_info(sentence):
    # Load SpaCy model
    nlp = spacy.load("en_core_web_md")
    
    # Initialize variables
    property_type = []
    bedroom_number = None
    price = None
    location = []

    # Mapping numbers written as words to integers
    word_to_number = {
        "zero": 0,
        "one": 1,
        "two": 2,
        "three": 3,
        "four": 4,
        "five": 5,
        "six": 6,
        "seven": 7,
        "eight": 8,
        "nine": 9,
        "ten": 10,
    }

    # Extract bedroom number (we explicitly match the number and the word 'bedroom')
    bedroom_match = re.search(r"(\d+|one|two|three|four|five|six|seven|eight|nine|ten)[\s-]?(bedroom|room)", sentence)
    if bedroom_match:
        bedroom_number = bedroom_match.group(1)
        if bedroom_number.isdigit():
            bedroom_number = int(bedroom_number)
        else:
            bedroom_number = word_to_number.get(bedroom_number.lower(), None)
        # Remove the bedroom number from the sentence to avoid matching it as price
        sentence = sentence.replace(bedroom_match.group(0), "")

    # Use regex to extract price (after removing bedroom number to avoid matching it)
    price_match = re.search(r"\$?(\d{1,3}(?:[.,]?\d+)*(?:[KMkm])?)", sentence)
    if price_match:
        raw_price = price_match.group(0)
        # Clean the price string by removing '$' and commas
        raw_price_cleaned = raw_price.replace("$", "").replace(",", "").replace('k', 'K').replace('m', 'M')
        
        # Handle K/M notation
        if "K" in raw_price_cleaned.upper():
            price = int(float(raw_price_cleaned.replace("K", "")) * 1000)  # Multiply by 1 thousand for K
        elif "M" in raw_price_cleaned.upper():
            price = int(float(raw_price_cleaned.replace("M", "")) * 1_000_000)  # Multiply by 1 million for M
        else:
            price = int(raw_price_cleaned)  # Convert directly to int
        sentence = sentence.replace(price_match.group(0), "")

    # Tokenize the sentence and apply NLP on it
    doc = nlp(sentence)

    # Extract property type
    property_types = ["apartment", "house", "villa", "condo", "land", "building", 'flat', 'condominium', 'penthouse', 'palace']
    for token in doc:
        if token.text.lower().rstrip('s') in property_types:
            property_type.append(token.text)

    # Extract location
    for ent in doc.ents:
        if ent.label_ in ["GPE", "LOC"]:  # GPE: Geo-political entity
            location.append(ent.text)

    return {
        "type": property_type,
        "bedroom_number": bedroom_number,
        "price": price,
        "location": location
    }

result = extract_info(sys.argv[1])
print(json.dumps(result))
