from flask import Flask, jsonify, request, make_response, redirect, url_for

app = Flask(__name__)

@app.route("/", methods=['GET', 'POST'])
def index():
    if (request.method == 'POST'):
        some_json = request.get_json()
        print(some_json)
        return jsonify({'you sent': some_json}), 201
    else:
        return jsonify({"about": "Hello World!", 'method' : request.method}), 201

@app.route("/<string:name>", methods=['POST'])
def name():
    if (request.method == 'POST'):
        some_json = request.get_json()
        return jsonify({'you sent': some_json}), 201
    
@app.route('/multi/<int:num>', methods=['GET'])
def get_multiply10(num):
    return jsonify({'result': num * 10})

if __name__ == '__main__':
    app.run(debug=True)



# calling the endpoint
# curl http://127.0.0.1:5000
# calling the endpoint in verbose mode
# curl -v http://127.0.0.1:5000
# calling post endpoint
# curl -H "Content-Type: application/json" -X POST -d '{"name": "xyz", "address": "xyz jason street"}'
#  http://127.0.0.1:5000

# calling the endpoint with url params
# curl -v http://127.0.0.1:5000/multi/44
